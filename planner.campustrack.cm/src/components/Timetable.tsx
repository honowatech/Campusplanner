import React, { useState, useMemo } from 'react';
import {
  Course,
  Teacher,
  Room,
  Department,
  CourseClass,
  AppSettings,
  ShiftPlanning,
  Planning,
} from '@/src/lib/types';
import {
  MapPin,
  Plus,
  Filter,
  Users,
  Layers,
  FileText,
  FileSpreadsheet,
  Download,
  LayoutGrid,
  List,
  X,
  AlertTriangle,
  Calendar,
  Clock,
  Trash2,
} from 'lucide-react';
import { Modal } from './Modal';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';
import { TIME_SLOTS } from '../lib/constants';

export type TimetableProps = {
  courses: Course[];
  teachers: Teacher[];
  rooms: Room[];
  departments: Department[];
  classes: CourseClass[];
  shiftPlannings: ShiftPlanning[];
  planning: Planning;
  onAddShift: (shift: Omit<ShiftPlanning, 'id' | 'created_at' | 'updated_at'>) => void;
  onDeleteShift: (id: number) => void;
  settings: AppSettings;
};

type ViewMode = 'standard' | 'global';

export const Timetable: React.FC<TimetableProps> = ({
  courses,
  teachers,
  rooms,
  departments,
  classes,
  shiftPlannings,
  planning,
  onDeleteShift,
  onAddShift,
  settings,
}) => {
  const { t } = useTranslation();
  const [viewMode, setViewMode] = useState<ViewMode>('standard');
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [isExportMenuOpen, setIsExportMenuOpen] = useState(false);
  const [conflictError, setConflictError] = useState<string | null>(null);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [shiftToDelete, setShiftToDelete] = useState<number | null>(null);

  const [selectedDeptId, setSelectedDeptId] = useState<number | ''>('');
  const [selectedMajor, setSelectedMajor] = useState<string>('');
  const [selectedGroupId, setSelectedGroupId] = useState<number | ''>('');

  const [formData, setFormData] = useState<Partial<ShiftPlanning>>({
    course_id: undefined,
    course_class_id: undefined,
    teacher_id: undefined,
    room_id: undefined,
    date: formatDateTime(new Date().toISOString().split('T')[0]),
    starting_hour: '08:00:00',
    ending_hour: '10:00:00',
    number_teachers: 1,
    status: 'pending',
  });

  const filteredShiftPlannings = useMemo(() => {
    return shiftPlannings?.filter((shift) => {
      if (selectedGroupId) {
        return shift.course_class_id === selectedGroupId;
      }
      if (selectedMajor) {
        const classItem = classes.find((course_class) => course_class.id === shift.course_class_id);
        return (
          classItem?.level === selectedMajor &&
          (!selectedDeptId || classItem?.department_id === Number(selectedDeptId))
        );
      }
      if (selectedDeptId) {
        const classItem = classes.find((course_class) => course_class.id === shift.course_class_id);
        return classItem?.department_id === selectedDeptId;
      }
      return true;
    });
  }, [shiftPlannings, selectedGroupId, selectedMajor, selectedDeptId, classes]);

  const uniqueDates = useMemo(() => {
    const dates = new Set(filteredShiftPlannings?.map((shiftPlanning) => shiftPlanning.date) || []);
    return Array.from(dates).sort();
  }, [filteredShiftPlannings]);

  const availableMajors = useMemo(() => {
    let filteredClasses = classes;
    if (selectedDeptId) {
      filteredClasses = filteredClasses.filter(
        (course_class) => course_class.department_id === selectedDeptId,
      );
    }
    return Array.from(
      new Set(filteredClasses?.map((course_class) => course_class.level).filter(Boolean)),
    ).sort();
  }, [classes, selectedDeptId]);

  const filteredClasses = useMemo(() => {
    let filtered = classes;
    if (selectedDeptId) {
      filtered = filtered.filter(
        (course_class) => course_class.department_id === Number(selectedDeptId),
      );
    }
    if (selectedMajor) {
      filtered = filtered.filter((course_class) => course_class.level === selectedMajor);
    }
    return filtered;
  }, [classes, selectedDeptId, selectedMajor]);

  const getTeacher = (id?: number) => teachers.find((teacher) => teacher.id === id);
  const getClass = (id?: number) => classes.find((course_class) => course_class.id === id);
  const getRoom = (id?: number) => rooms.find((room) => room.id === id);
  const getCourse = (id: number) => courses.find((course) => course.id === id);

  const getShiftsForSlot = (date: string, startHour: string, endHour: string) => {
    // console.log(
    //   filteredShiftPlannings?.[0].date === date,
    //   filteredShiftPlannings?.[0].starting_hour.slice(0, 5) === startHour,
    // );
    return filteredShiftPlannings?.filter(
      (shift) =>
        shift.date === date &&
        shift.starting_hour.slice(0, 5) === startHour &&
        shift.ending_hour.slice(0, 5) === endHour,
    );
  };

  const getShiftForGroupSlot = (
    date: string,
    startHour: string,
    endHour: string,
    groupId: number,
  ) => {
    return filteredShiftPlannings?.find(
      (shift) =>
        shift.date === date &&
        shift.starting_hour.slice(0, 5) === startHour &&
        shift.ending_hour.slice(0, 5) === endHour &&
        shift.course_class_id === groupId,
    );
  };

  const validateConflicts = (
    newShift: Partial<ShiftPlanning>,
    ignoreShiftId?: number,
  ): string | null => {
    const sameTimeShifts = shiftPlannings.filter(
      (shift) =>
        shift.date === newShift.date &&
        (shift.starting_hour ?? '').slice(0, 5) === (newShift.starting_hour ?? '').slice(0, 5) &&
        (shift.ending_hour ?? '').slice(0, 5) === (newShift.ending_hour ?? '').slice(0, 5) &&
        shift.id !== ignoreShiftId,
    );

    if (settings.enableRoomConflict && newShift.room_id) {
      const roomBusy = sameTimeShifts.some((shift) => shift.room_id === newShift.room_id);
      if (roomBusy) return t('conflictRoomMsg');
    }

    if (settings.enableTeacherConflict && newShift.teacher_id) {
      const teacherBusy = sameTimeShifts.some((shift) => shift.teacher_id === newShift.teacher_id);
      if (teacherBusy) return t('conflictTeacherMsg');
    }

    if (settings.enableGroupConflict && newShift.course_class_id) {
      const groupBusy = sameTimeShifts.some(
        (shift) => shift.course_class_id === newShift.course_class_id,
      );
      if (groupBusy) return t('conflictGroupMsg');
    }

    return null;
  };

  const handleAddSchedule = (
    date?: string,
    startHour?: string,
    endHour?: string,
    groupId?: number,
  ) => {
    setFormData({
      course_id: courses.length > 0 ? courses[0].id : 0,
      course_class_id: groupId || (classes.length > 0 ? classes[0].id : undefined),
      teacher_id: teachers.length > 0 ? teachers[0].id : undefined,
      room_id: rooms.length > 0 ? rooms[0].id : undefined,
      date: date || formatDateTime(new Date().toISOString().split('T')[0]),
      starting_hour: startHour || '08:00:00',
      ending_hour: endHour || '10:00:00',
      number_teachers: 1,
      status: 'pending',
    });
    setConflictError(null);
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (
      !formData.course_id ||
      !formData.course_class_id ||
      !formData.date ||
      !formData.starting_hour ||
      !formData.ending_hour
    )
      return;

    // Validate shift constraints
    // const startHour = parseInt(formData.starting_hour!);
    // const endHour = parseInt(formData.ending_hour!);

    // Check duration max 4 hours
    // if (endHour - startHour > 4) {
    //   setConflictError(t("shiftMaxDuration"));
    //   return;
    // }

    // Check lunch break (12h-13h)
    // if (startHour < 13 && endHour > 12) {
    //   setConflictError(t("shiftLunchBreak"));
    //   return;
    // }

    const error = validateConflicts(formData);
    if (error) {
      setConflictError(error);
      return;
    }

    onAddShift({
      planning_id: planning.id,
      course_id: formData.course_id!,
      course_class_id: formData.course_class_id!,
      teacher_id: formData.teacher_id,
      room_id: formData.room_id,
      date: formData.date!,
      starting_hour: formData.starting_hour!,
      ending_hour: formData.ending_hour!,
      number_teachers: formData.number_teachers || 1,
      status: formData.status || 'pending',
      notes: formData.notes,
    });
    setIsModalOpen(false);
  };

  const requestDelete = (id: number) => {
    setShiftToDelete(id);
    setIsDeleteModalOpen(true);
  };

  const confirmDelete = () => {
    if (shiftToDelete) {
      onDeleteShift(shiftToDelete);
      setIsDeleteModalOpen(false);
      setShiftToDelete(null);
    }
  };

  const handleDeptChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    setSelectedDeptId(Number(e.target.value));
    setSelectedMajor('');
    setSelectedGroupId('');
  };

  const handleMajorChange = (e: React.ChangeEvent<HTMLSelectElement>) => {
    setSelectedMajor(e.target.value);
    setSelectedGroupId('');
  };

  return (
    <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden flex flex-col h-full">
      <div className="p-6 border-b border-gray-100">
        <div className="flex flex-col lg:flex-row lg:items-center justify-between mb-4">
          <div className="flex items-center mb-4 lg:mb-0">
            <Clock size={18} className="text-secondary" />
            <h1 className="font-bold text-xl text-gray-900 ml-2">{t('timetable')}</h1>
            <span className="ml-3 text-sm text-gray-500">
              ({shiftPlannings.length} {t('shifts')})
            </span>
          </div>
          <div className="flex items-center mb-4 lg:mb-0">
            {`From ${formatDateTime(planning?.starting_date, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })} to ${formatDateTime(planning?.ending_date, { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' })}`}
          </div>
          <div className="flex items-center space-x-2">
            <button
              onClick={() => handleAddSchedule()}
              className="flex items-center space-x-1 p-3 bg-secondary text-white rounded-md hover:bg-primary transition-colors text-sm font-medium"
            >
              <Plus size={14} />
              <span>{t('addShift')}</span>
            </button>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-3">
          <div className="bg-gray-100 p-1 rounded-lg flex items-center border border-gray-200">
            <button
              onClick={() => setViewMode('standard')}
              className={`p-1.5 rounded-md transition-all ${viewMode === 'standard' ? 'bg-white shadow-sm text-secondary' : 'text-gray-500 hover:text-gray-700'}`}
            >
              <LayoutGrid size={18} />
            </button>
            <button
              onClick={() => setViewMode('global')}
              className={`p-1.5 rounded-md transition-all ${viewMode === 'global' ? 'bg-white shadow-sm text-secondary' : 'text-gray-500 hover:text-gray-700'}`}
            >
              <List size={18} className="rotate-90" />
            </button>
          </div>

          <div className="h-8 w-px bg-gray-200 hidden sm:block"></div>

          <div className="flex items-center bg-gray-50 rounded-lg p-1 border border-gray-200">
            <Filter size={16} className="text-gray-400 ml-2" />
            <select
              className="bg-transparent border-none text-sm focus:ring-0 text-gray-700 py-1"
              value={selectedDeptId}
              onChange={handleDeptChange}
            >
              <option value="">{t('allDepts')}</option>
              {departments?.map((department) => (
                <option key={department.id} value={department.id}>
                  {department.name}
                </option>
              ))}
            </select>
          </div>

          <div className="flex items-center bg-gray-50 rounded-lg p-1 border border-gray-200">
            <Layers size={16} className="text-gray-400 ml-2" />
            <select
              className="bg-transparent border-none text-sm focus:ring-0 text-gray-700 py-1"
              value={selectedMajor}
              onChange={handleMajorChange}
            >
              <option value="">{t('allMajors')}</option>
              {availableMajors.map((major) => (
                <option key={major} value={major}>
                  {major}
                </option>
              ))}
            </select>
          </div>

          {viewMode === 'standard' && (
            <div className="flex items-center bg-gray-50 rounded-lg p-1 border border-gray-200">
              <Users size={16} className="text-gray-400 ml-2" />
              <select
                className="bg-transparent border-none text-sm focus:ring-0 text-gray-700 py-1"
                value={selectedGroupId}
                onChange={(e) => setSelectedGroupId(Number(e.target.value))}
              >
                <option value="">{t('allClasses')}</option>
                {filteredClasses?.map((course_class) => (
                  <option key={course_class.id} value={course_class.id}>
                    {course_class.name}
                  </option>
                ))}
              </select>
            </div>
          )}

          <div className="relative">
            <button
              onClick={() => setIsExportMenuOpen(!isExportMenuOpen)}
              className="flex items-center justify-center p-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors border border-gray-200"
            >
              <Download size={20} />
            </button>
            {isExportMenuOpen && (
              <div className="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg py-1 border border-gray-100 z-50">
                <button className="w-full text-left px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 flex items-center">
                  <FileText size={16} className="mr-2 text-red-500" />
                  {t('exportPDF')}
                </button>
                <button className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center">
                  <FileSpreadsheet size={16} className="mr-2 text-green-600" />
                  {t('exportExcel')}
                </button>
              </div>
            )}
          </div>
        </div>
      </div>

      <div className="flex-1 overflow-auto bg-gray-50/50 p-4">
        {uniqueDates.length === 0 ? (
          <div className="flex flex-col items-center justify-center h-full text-gray-400">
            <Calendar size={64} className="mb-4 opacity-20" />
            <p className="text-lg font-medium">{t('noShifts')}</p>
            <button
              onClick={() => handleAddSchedule()}
              className="mt-4 text-secondary hover:underline"
            >
              {t('createShift')}
            </button>
          </div>
        ) : viewMode === 'standard' ? (
          <div className="space-y-6">
            {uniqueDates.map((date) => (
              <div key={date} className="bg-white rounded-lg shadow-sm border border-gray-200">
                <div className="p-3 bg-indigo-50 border-b border-indigo-100 rounded-t-lg">
                  <h3 className="font-semibold text-secondary">{formatDateTime(date)}</h3>
                </div>
                <div className="divide-y divide-gray-100">
                  {TIME_SLOTS.map((slot) => {
                    const slotShifts = getShiftsForSlot(date, slot.start, slot.end);
                    return (
                      <div key={slot.label} className="grid grid-cols-[120px_1fr]">
                        <div className="p-3 text-sm text-gray-500 font-medium border-r border-gray-100">
                          {slot.label}
                        </div>
                        <div className="p-2 min-h-[80px]">
                          {slotShifts.length > 0 ? (
                            <div className="flex flex-wrap gap-2">
                              {slotShifts.map((shift) => {
                                const course = getCourse(shift.course_id);
                                const classItem = getClass(shift.course_class_id);
                                const teacher = getTeacher(shift.teacher_id);
                                const room = getRoom(shift.room_id);
                                return (
                                  <div
                                    key={shift.id}
                                    className="bg-white rounded-md p-2 shadow-sm border border-gray-200 min-w-[200px]"
                                  >
                                    <div className="flex justify-between items-start">
                                      <div>
                                        <span className="font-bold text-xs text-gray-900">
                                          {course?.code ||
                                            course?.name ||
                                            `Course ${shift.course_id}`}
                                        </span>
                                        {classItem && (
                                          <span className="ml-2 text-[10px] bg-gray-100 px-1 rounded">
                                            {classItem.code}
                                          </span>
                                        )}
                                      </div>
                                      <button
                                        onClick={() => requestDelete(shift.id)}
                                        className="p-1 text-gray-400 hover:text-red-600"
                                      >
                                        <Trash2 size={12} />
                                      </button>
                                    </div>
                                    {teacher && (
                                      <div className="text-[10px] text-gray-600 mt-1">
                                        {teacher.full_name}
                                      </div>
                                    )}
                                    {room && (
                                      <div className="flex items-center text-[10px] text-gray-500 mt-1">
                                        <MapPin size={10} className="mr-1" />
                                        {room.name}
                                      </div>
                                    )}
                                  </div>
                                );
                              })}
                            </div>
                          ) : (
                            <button
                              onClick={() => handleAddSchedule(date, slot.start, slot.end)}
                              className="text-xs text-gray-400 hover:text-secondary font-medium size-full"
                            >
                              + {t('add')}
                            </button>
                          )}
                        </div>
                      </div>
                    );
                  })}
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="overflow-x-auto">
            <table className="w-full border-collapse">
              <thead className="sticky top-0 z-10 bg-white shadow-sm">
                <tr>
                  <th className="p-4 font-semibold text-gray-500 text-sm text-left border-b border-r border-gray-200 bg-gray-50 w-32">
                    {t('timeDay')}
                  </th>
                  {filteredClasses.map((classItem) => (
                    <th
                      key={classItem.id}
                      className="p-4 font-bold text-gray-700 text-center border-b border-r border-gray-200 min-w-[160px] bg-gray-50"
                    >
                      <div className="flex flex-col items-center">
                        <span>{classItem.code || classItem.name}</span>
                      </div>
                    </th>
                  ))}
                </tr>
              </thead>
              <tbody>
                {uniqueDates.map((date) => (
                  <React.Fragment key={date}>
                    <tr className="bg-indigo-50">
                      <td
                        colSpan={filteredClasses.length + 1}
                        className="p-2 font-semibold text-secondary text-sm"
                      >
                        {formatDateTime(date)}
                      </td>
                    </tr>
                    {TIME_SLOTS.map((slot) => (
                      <tr key={`${date}-${slot.label}`} className="hover:bg-gray-50">
                        <td className="p-3 border-b border-r border-gray-200 bg-white text-sm text-gray-500">
                          {slot.label}
                        </td>
                        {filteredClasses.map((classItem) => {
                          const shift = getShiftForGroupSlot(
                            date,
                            slot.start,
                            slot.end,
                            classItem.id,
                          );
                          const course = shift ? getCourse(shift.course_id) : null;
                          const teacher = shift ? getTeacher(shift.teacher_id) : null;
                          const room = shift ? getRoom(shift.room_id) : null;
                          return (
                            <td
                              key={classItem.id}
                              className="p-2 border-b border-r border-gray-200 relative min-h-[80px]"
                            >
                              {shift ? (
                                <div className="bg-white rounded-md p-2 shadow-sm border border-gray-200 text-xs">
                                  <div className="flex justify-between items-start">
                                    <span className="font-bold text-gray-900">
                                      {course?.code || course?.name || `Course ${shift.course_id}`}
                                    </span>
                                    <button
                                      onClick={() => requestDelete(shift.id)}
                                      className="text-gray-400 hover:text-red-600"
                                    >
                                      <X size={12} />
                                    </button>
                                  </div>
                                  {teacher && (
                                    <div className="text-gray-600 mt-1">{teacher.full_name}</div>
                                  )}
                                  {room && (
                                    <div className="flex items-center text-gray-500 mt-1">
                                      <MapPin size={10} className="mr-1" />
                                      {room.name}
                                    </div>
                                  )}
                                </div>
                              ) : (
                                <button
                                  onClick={() =>
                                    handleAddSchedule(date, slot.start, slot.end, classItem.id)
                                  }
                                  className="size-full text-xs text-gray-400 hover:text-secondary font-medium opacity-0 hover:opacity-100 transition-opacity"
                                >
                                  + {t('add')}
                                </button>
                              )}
                            </td>
                          );
                        })}
                      </tr>
                    ))}
                  </React.Fragment>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>

      <Modal isOpen={isModalOpen} onClose={() => setIsModalOpen(false)} title={t('addShift')}>
        <form onSubmit={handleSubmit} className="space-y-4">
          {conflictError && (
            <div className="p-3 bg-red-50 border border-red-200 rounded-lg flex items-start space-x-3">
              <AlertTriangle size={20} className="text-red-600 shrink-0 mt-0.5" />
              <div className="text-sm text-red-700">
                <p className="font-bold">{t('conflictError')}</p>
                <p>{conflictError}</p>
              </div>
            </div>
          )}

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('course')}</label>
            <select
              required
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.course_id}
              onChange={(e) => setFormData({ ...formData, course_id: Number(e.target.value) })}
            >
              <option value={0} disabled>
                {t('selectCourse')}
              </option>
              {courses?.map((course) => (
                <option key={course.id} value={course.id}>
                  {course.code} - {course.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('classes')}</label>
            <select
              required
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.course_class_id}
              onChange={(e) =>
                setFormData({
                  ...formData,
                  course_class_id: Number(e.target.value),
                })
              }
            >
              <option value={0} disabled>
                {t('selectClass')}
              </option>
              {classes?.map((course_class) => (
                <option key={course_class.id} value={course_class.id}>
                  {course_class.name} ({course_class.code})
                </option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('date')}</label>
              <input
                required
                type="date"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={(formData.date ?? '').slice(0, 10)}
                onChange={(e) => setFormData({ ...formData, date: e.target.value })}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('teachers')}
              </label>
              <select
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.teacher_id || ''}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    teacher_id: e.target.value ? Number(e.target.value) : undefined,
                  })
                }
              >
                <option value="">{t('selectTeacher')}</option>
                {teachers?.map((teacher) => (
                  <option key={teacher.id} value={teacher.id}>
                    {teacher.full_name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-3 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('startTime')}
              </label>
              <select
                required
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.starting_hour}
                onChange={(e) => setFormData({ ...formData, starting_hour: e.target.value })}
              >
                <option value="08:00">08:00</option>
                <option value="09:00">09:00</option>
                <option value="10:00">10:00</option>
                <option value="11:00">11:00</option>
                <option value="13:00">13:00</option>
                <option value="14:00">14:00</option>
                <option value="15:00">15:00</option>
                <option value="16:00">16:00</option>
                <option value="17:00">17:00</option>
                <option value="18:00">18:00</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('endTime')}</label>
              <select
                required
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.ending_hour}
                onChange={(e) => setFormData({ ...formData, ending_hour: e.target.value })}
              >
                <option value="09:00">09:00</option>
                <option value="10:00">10:00</option>
                <option value="11:00">11:00</option>
                <option value="12:00">12:00</option>
                <option value="14:00">14:00</option>
                <option value="15:00">15:00</option>
                <option value="16:00">16:00</option>
                <option value="17:00">17:00</option>
                <option value="18:00">18:00</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('room')}</label>
              <select
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.room_id || ''}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    room_id: e.target.value ? Number(e.target.value) : undefined,
                  })
                }
              >
                <option value="">{t('selectRoom')}</option>
                {rooms?.map((room) => (
                  <option key={room.id} value={room.id}>
                    {room.name}
                  </option>
                ))}
              </select>
            </div>
          </div>

          <div className="flex justify-end space-x-2 pt-4">
            <button
              type="button"
              onClick={() => setIsModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors"
            >
              {t('save')}
            </button>
          </div>
        </form>
      </Modal>

      <Modal
        isOpen={isDeleteModalOpen}
        onClose={() => setIsDeleteModalOpen(false)}
        title={t('confirmDelete')}
      >
        <div className="p-4">
          <p className="text-gray-700 mb-6">{t('confirmDeleteShift')}</p>
          <div className="flex justify-end space-x-2">
            <button
              onClick={() => setIsDeleteModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              onClick={confirmDelete}
              className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
            >
              {t('delete')}
            </button>
          </div>
        </div>
      </Modal>
    </div>
  );
};
