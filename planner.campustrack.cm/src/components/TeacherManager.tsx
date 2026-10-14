import { SmartPagination } from '@/src/components/SmartPagination';
import React, { useState, useMemo } from 'react';
import { Teacher, Department, User, CourseClass, ShiftPlanning } from '@/src/lib/types';
import { Modal } from './Modal';
import {
  Plus,
  Edit2,
  Trash2,
  Search,
  Mail,
  Briefcase,
  Filter,
  Lock,
  ArrowLeft,
  Clock,
  BookOpen,
  MapPin,
  Calendar,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';

type TeacherManagerProps = {
  teachers: Teacher[];
  departments: Department[];
  classes: CourseClass[];
  user: User;
  shiftPlannings?: ShiftPlanning[];
  currentPage?: number;
  totalPages?: number;
  onPageChange?: (page: number) => void;
  readOnly?: boolean;
  searchTerm: string;
  filterDept: string;
  onSearchChange: (value: string) => void;
  onFilterDeptChange: (value: string) => void;
  onAddTeacher: (teacher: Teacher) => void;
  onUpdateTeacher: (teacher: Teacher) => void;
  onDeleteTeacher: (id: number) => void;
};

export const TeacherManager: React.FC<TeacherManagerProps> = ({
  teachers,
  departments,
  classes,
  user,
  shiftPlannings,
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  readOnly = false,
  searchTerm,
  filterDept,
  onSearchChange,
  onFilterDeptChange,
  onAddTeacher,
  onUpdateTeacher,
  onDeleteTeacher,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingTeacher, setEditingTeacher] = useState<Teacher | null>(null);
  const [selectedTeacherId, setSelectedTeacherId] = useState<number | null>(null);
  const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
  const [teacherToDelete, setTeacherToDelete] = useState<number | null>(null);
  const [shiftCurrentPage, setShiftCurrentPage] = useState(1);
  const SHIFT_ITEMS_PER_PAGE = 10;

  const [formData, setFormData] = useState<Partial<Teacher>>({
    first_name: '',
    last_name: '',
    email: '',
    department_id: user.department_id,
    speciality: '',
    color: '#3b82f6',
    phone: '',
    // address: "",
    // bio: "",
  });

  // Calculations for Detail View
  const selectedTeacherStats = useMemo(() => {
    if (!selectedTeacherId) return null;

    const teacherShifts =
      shiftPlannings?.filter((shift) => shift.teacher_id === selectedTeacherId) || [];

    const totalHours = teacherShifts.reduce((sum, shift) => {
      const start = shift.starting_hour.split(':');
      const end = shift.ending_hour.split(':');
      const hours =
        parseInt(end[0]) + parseInt(end[1]) / 60 - (parseInt(start[0]) + parseInt(start[1]) / 60);
      return sum + hours;
    }, 0);

    const uniqueClassIds = Array.from(
      new Set(teacherShifts?.map((shift) => shift.course_class_id)),
    );
    const assignedClasses = classes.filter((c) => uniqueClassIds.includes(c.id));

    return {
      shifts: teacherShifts,
      totalHours,
      assignedClasses,
    };
  }, [selectedTeacherId, shiftPlannings, classes]);

  const handleOpenModal = (teacher?: Teacher) => {
    if (teacher) {
      setEditingTeacher(teacher);
      setFormData(teacher);
    } else {
      setEditingTeacher(null);
      setFormData({
        first_name: '',
        last_name: '',
        email: '',
        // Pour un responsable, force son département ; sinon le premier
        // département disponible (l'admin pourra le modifier dans le formulaire).
        department_id:
          user.role === 'responsable-departement'
            ? user.department_id
            : departments.length > 0
              ? departments[0].id
              : undefined,
        speciality: '',
        color: '#3b82f6',
        phone: '',
        // address: "",
        // bio: "",
      });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.first_name || !formData.last_name || !formData.email) return;

    if (editingTeacher) {
      onUpdateTeacher({ ...editingTeacher, ...formData } as Teacher);
    } else {
      onAddTeacher({
        teacher: formatDateTime(new Date().toISOString().split('T')[0]),
        ...formData,
      } as Teacher);
    }
    setIsModalOpen(false);
  };

  const requestDelete = (id: number) => {
    setTeacherToDelete(id);
    setIsDeleteModalOpen(true);
  };

  const confirmDelete = () => {
    if (teacherToDelete) {
      onDeleteTeacher(teacherToDelete);
      setIsDeleteModalOpen(false);
      setTeacherToDelete(null);
    }
  };

  const handleTeacherClick = (id: number) => {
    setSelectedTeacherId(id);
    setShiftCurrentPage(1);
  };

  const getClass = (id: number) => classes.find((course_class) => course_class.id === id);

  // Render Detail View
  if (selectedTeacherId) {
    const teacher = teachers.find((teacher) => teacher.id === selectedTeacherId);
    if (!teacher) return <div>Teacher not found</div>;
    const stats = selectedTeacherStats!;

    const paginatedShifts =
      stats.shifts?.slice(
        (shiftCurrentPage - 1) * SHIFT_ITEMS_PER_PAGE,
        shiftCurrentPage * SHIFT_ITEMS_PER_PAGE,
      ) || [];
    const shiftTotalPages = Math.ceil((stats.shifts?.length || 0) / SHIFT_ITEMS_PER_PAGE);

    return (
      <div className="animate-in fade-in duration-300 space-y-6">
        {/* Header / Back Button */}
        <div className="flex items-center space-x-4 mb-2">
          <button
            type="button"
            onClick={() => setSelectedTeacherId(null)}
            className="p-2 rounded-full bg-white border border-gray-200 hover:bg-gray-50 text-gray-600 transition-colors"
          >
            <ArrowLeft size={20} />
          </button>
          <h2 className="text-2xl font-bold text-gray-900">{t('teacherDetails')}</h2>
        </div>

        {/* Profile Card */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="h-24" style={{ backgroundColor: teacher.color }}></div>
          <div className="px-8 pb-8 relative">
            <div className="absolute -top-12 left-8">
              <div
                className="size-24 rounded-full border-4 border-white bg-white shadow-md flex items-center justify-center text-3xl font-bold text-gray-200"
                style={{ backgroundColor: teacher.color }}
              >
                {`${teacher.first_name.charAt(0)}${teacher.last_name.charAt(0)}`}
              </div>
            </div>
            <div className="ml-32 pt-2 flex justify-between items-start">
              <div>
                <h1 className="text-2xl font-bold text-gray-900">{`${teacher.full_name}`}</h1>
                <p className="text-secondary font-medium">{teacher.speciality}</p>
              </div>
              <div className="flex space-x-2">
                {!readOnly && (
                  <button
                    type="button"
                    onClick={() => handleOpenModal(teacher)}
                    className="px-3 py-1.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors flex items-center"
                  >
                    <Edit2 size={14} className="mr-1.5" />
                    {t('edit')}
                  </button>
                )}
              </div>
            </div>

            {/* <div className="mt-6 space-y-6">
              {teacher.bio && (
                <div className="bg-gray-50 p-4 rounded-lg border border-gray-100">
                  <h4 className="text-sm font-semibold text-gray-900 mb-2 uppercase tracking-wide flex items-center">
                    <UserIcon size={14} className="mr-2" />
                    {t("aboutTeacher")}
                  </h4>
                  <p className="text-gray-600 text-sm leading-relaxed">
                    {teacher.bio}
                  </p>
                </div>
              )}

              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div className="flex items-center text-gray-600">
                  <Mail size={18} className="mr-2 text-gray-400" />
                  <span className="text-sm">{teacher.email}</span>
                </div>
                <div className="flex items-center text-gray-600">
                  <Briefcase size={18} className="mr-2 text-gray-400" />
                  <span className="text-sm">{teacher.department}</span>
                </div>
                {teacher.phone && (
                  <div className="flex items-center text-gray-600">
                    <Phone size={18} className="mr-2 text-gray-400" />
                    <span className="text-sm">{teacher.phone}</span>
                  </div>
                )}
                {teacher.address && (
                  <div className="flex items-center text-gray-600">
                    <Home size={18} className="mr-2 text-gray-400" />
                    <span className="text-sm truncate" title={teacher.address}>
                      {teacher.address}
                    </span>
                  </div>
                )}
              </div>
            </div> */}
          </div>
        </div>

        {/* Stats Grid */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
          <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div className="p-3 bg-blue-50 text-blue-600 rounded-lg">
              <Clock size={24} />
            </div>
            <div>
              <p className="text-sm text-gray-500 font-medium">{t('weeklyHours')}</p>
              <h3 className="text-2xl font-bold text-gray-900">
                {isNaN(stats.totalHours) ? 0 : stats.totalHours}h
              </h3>
            </div>
          </div>

          <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div className="p-3 bg-purple-50 text-purple-600 rounded-lg">
              <BookOpen size={24} />
            </div>
            <div>
              <p className="text-sm text-gray-500 font-medium">{t('assignedClasses')}</p>
              <h3 className="text-2xl font-bold text-gray-900">{stats.assignedClasses.length}</h3>
            </div>
          </div>

          <div className="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center space-x-4">
            <div className="p-3 bg-emerald-50 text-emerald-600 rounded-lg">
              <Calendar size={24} />
            </div>
            <div>
              <p className="text-sm text-gray-500 font-medium">{t('totalSessions')}</p>
              <h3 className="text-2xl font-bold text-gray-900">{stats.shifts?.length}</h3>
            </div>
          </div>
        </div>

        {/* Schedule / Shifts List */}
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div className="p-6 border-b border-gray-100">
            <h3 className="text-lg font-bold text-gray-900">{t('teachingSchedule')}</h3>
          </div>
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse">
              <thead>
                <tr className="bg-gray-50 text-xs uppercase text-gray-500 font-semibold tracking-wider">
                  <th className="p-4">{t('courseCode')}</th>
                  <th className="p-4">{t('courseName')}</th>
                  <th className="p-4">{t('classDisplayName')}</th>
                  <th className="p-4">{t('date')}</th>
                  <th className="p-4">{t('timeSlot')}</th>
                  <th className="p-4">{t('roomName')}</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {paginatedShifts.length > 0 ? (
                  paginatedShifts.map((shift) => {
                    const classItem = getClass(shift.course_class_id);
                    return (
                      <tr key={shift.id} className="hover:bg-gray-50 transition-colors text-sm">
                        <td className="p-4 font-mono text-gray-700">{shift.course?.code}</td>
                        <td className="p-4 font-medium text-gray-900">{shift.course?.name}</td>
                        <td className="p-4 text-gray-600">{classItem?.name || '-'}</td>
                        <td className="p-4 text-gray-600">
                          <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            {formatDateTime(shift.date)}
                          </span>
                        </td>
                        <td className="p-4 text-gray-600">
                          {shift.starting_hour} - {shift.ending_hour}
                        </td>
                        <td className="p-4 text-gray-600 flex items-center">
                          <MapPin size={14} className="mr-1.5 text-gray-400" />- {shift.room?.name}
                        </td>
                      </tr>
                    );
                  })
                ) : (
                  <tr>
                    <td colSpan={6} className="p-8 text-center text-gray-500">
                      {t('noClassesAssigned')}
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          <SmartPagination
            currentPage={shiftCurrentPage}
            totalPages={shiftTotalPages}
            onPageChange={setShiftCurrentPage}
          />
        </div>

        <Modal
          isOpen={isModalOpen}
          onClose={() => setIsModalOpen(false)}
          title={editingTeacher ? t('editTeacher') : t('newTeacher')}
        >
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label htmlFor="teacher-detail-first-name" className="block text-sm font-medium text-gray-700 mb-1">
                {t('firstName')}
              </label>
              <input
                id="teacher-detail-first-name"
                required
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.first_name}
                onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="teacher-detail-last-name" className="block text-sm font-medium text-gray-700 mb-1">
                {t('lastName')}
              </label>
              <input
                id="teacher-detail-last-name"
                required
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.last_name}
                onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
              />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label htmlFor="teacher-detail-email" className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
                <input
                  id="teacher-detail-email"
                  required
                  type="email"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                />
              </div>
              <div>
                <label htmlFor="teacher-detail-phone" className="block text-sm font-medium text-gray-700 mb-1">
                  {t('phoneNumber')}
                </label>
                <input
                  id="teacher-detail-phone"
                  type="tel"
                  className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                  value={formData.phone}
                  onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                />
              </div>
            </div>

            <div className="grid grid-cols-2 gap-4">
              <div>
                <label htmlFor="teacher-detail-department" className="block text-sm font-medium text-gray-700 mb-1">
                  {t('department')}
                </label>
                <div className="relative">
                  <select
                    id="teacher-detail-department"
                    className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none ${user.role === 'responsable-departement' ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : ''}`}
                    value={formData.department_id}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        department_id: Number(e.target.value),
                      })
                    }
                    disabled={user.role === 'responsable-departement'}
                  >
                    {departments?.map((department) => (
                      <option key={department.id} value={department.id}>
                        {department.name}
                      </option>
                    ))}
                  </select>
                  {user.role === 'responsable-departement' && (
                    <Lock
                      size={14}
                      className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                    />
                  )}
                </div>
              </div>
              <div>
                <label htmlFor="teacher-detail-color" className="block text-sm font-medium text-gray-700 mb-1">
                  {t('colorTag')}
                </label>
                <div className="flex items-center space-x-2">
                  <input
                    id="teacher-detail-color"
                    type="color"
                    className="h-9 w-full rounded cursor-pointer border border-gray-300"
                    value={formData.color}
                    onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                  />
                </div>
              </div>
            </div>
            <div>
              <label htmlFor="teacher-detail-speciality" className="block text-sm font-medium text-gray-700 mb-1">
                {t('speciality')}
              </label>
              <input
                id="teacher-detail-speciality"
                required
                type="text"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.speciality}
                onChange={(e) => setFormData({ ...formData, speciality: e.target.value })}
              />
            </div>
            {/* <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t("address")}
            </label>
            <input
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.address}
              onChange={(e) =>
                setFormData({ ...formData, address: e.target.value })
              }
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t("bio")}
            </label>
            <textarea
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              rows={3}
              value={formData.bio}
              onChange={(e) =>
                setFormData({ ...formData, bio: e.target.value })
              }
            />
          </div> */}

            <div className="pt-4 flex justify-end space-x-3">
              <button
                type="button"
                onClick={() => setIsModalOpen(false)}
                className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
              >
                {t('cancel')}
              </button>
              <button
                type="submit"
                className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary font-medium transition-colors shadow-sm"
              >
                {editingTeacher ? t('save') : t('add')}
              </button>
            </div>
          </form>
        </Modal>
      </div>
    );
  }

  // Render List View
  return (
    <div className="space-y-6 h-full flex flex-col">
      <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div className="flex flex-col sm:flex-row gap-3 w-full lg:w-auto flex-1">
          {/* Search Bar */}
          <div className="relative w-full sm:w-72">
            <Search
              className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              size={20}
            />
            <input
              type="text"
              placeholder={t('searchTeachers')}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
              value={searchTerm}
              onChange={(e) => onSearchChange(e.target.value)}
            />
          </div>

          {/* Admin Filter Dropdown */}
          {user.role === 'administrateur' && (
            <div className="relative w-full sm:w-56">
              <Filter
                className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                size={18}
              />
              <select
                className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none appearance-none bg-white"
                value={filterDept}
                onChange={(e) => onFilterDeptChange(e.target.value)}
              >
                <option value="">{t('allDepts')}</option>
                {departments?.map((d) => (
                  <option key={d.id} value={d.id}>
                    {d.name}
                  </option>
                ))}
              </select>
            </div>
          )}
        </div>

        {!readOnly && (
          <button
            type="button"
            onClick={() => handleOpenModal()}
            className="flex items-center justify-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors w-full sm:w-auto font-medium"
          >
            <Plus size={20} />
            <span>{t('addTeacher')}</span>
          </button>
        )}
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 overflow-y-auto pb-4">
        {teachers?.map((teacher) => (
          <div
            key={teacher.id}
            className="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all group cursor-pointer"
            role="button"
            tabIndex={0}
            onClick={() => handleTeacherClick(teacher.id)}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                handleTeacherClick(teacher.id);
              }
            }}
          >
            <div className="p-6">
              <div className="flex justify-between items-start mb-4">
                <div
                  className="size-12 rounded-full flex items-center justify-center text-gray-200 font-bold text-xl"
                  style={{ backgroundColor: teacher.color }}
                >
                  {`${teacher.first_name.charAt(0)}${teacher.last_name.charAt(0)}`}
                </div>
                {!readOnly && (
                  <div className="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        handleOpenModal(teacher);
                      }}
                      className="p-2 text-gray-500 hover:text-secondary hover:bg-indigo-50 rounded-full"
                    >
                      <Edit2 size={16} />
                    </button>
                    <button
                      type="button"
                      onClick={(e) => {
                        e.stopPropagation();
                        requestDelete(teacher.id);
                      }}
                      className="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-full"
                    >
                      <Trash2 size={16} />
                    </button>
                  </div>
                )}
              </div>

              <h3 className="text-lg font-bold text-gray-900 mb-1">{`${teacher.full_name}`}</h3>
              <p className="text-sm text-secondary font-medium mb-4">{teacher.speciality}</p>

              <div className="space-y-2">
                <div className="flex items-center text-sm text-gray-600">
                  <Mail size={16} className="mr-2 text-gray-400" />
                  {teacher.email}
                </div>
                <div className="flex items-center text-sm text-gray-600">
                  <Briefcase size={16} className="mr-2 text-gray-400" />
                  {teacher.department?.name}
                </div>
              </div>
            </div>
            <div className="h-1 w-full rounded-b-xl" style={{ backgroundColor: teacher.color }} />
          </div>
        ))}
        {teachers && teachers.length === 0 && (
          <div className="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
            <p>{t('noResults')}</p>
          </div>
        )}
      </div>

      <SmartPagination
        currentPage={currentPage}
        totalPages={totalPages}
        onPageChange={onPageChange}
      />

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingTeacher ? t('editTeacher') : t('newTeacher')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label htmlFor="teacher-first-name" className="block text-sm font-medium text-gray-700 mb-1">{t('firstName')}</label>
            <input
              id="teacher-first-name"
              required
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.first_name}
              onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
            />
          </div>
          <div>
            <label htmlFor="teacher-last-name" className="block text-sm font-medium text-gray-700 mb-1">{t('lastName')}</label>
            <input
              id="teacher-last-name"
              required
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.last_name}
              onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="teacher-email" className="block text-sm font-medium text-gray-700 mb-1">{t('email')}</label>
              <input
                id="teacher-email"
                required
                type="email"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
              />
            </div>
            <div>
              <label htmlFor="teacher-phone" className="block text-sm font-medium text-gray-700 mb-1">
                {t('phoneNumber')}
              </label>
              <input
                id="teacher-phone"
                type="tel"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.phone}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
              />
            </div>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label htmlFor="teacher-department" className="block text-sm font-medium text-gray-700 mb-1">
                {t('department')}
              </label>
              <div className="relative">
                <select
                  id="teacher-department"
                  className={`w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none ${user.role === 'responsable-departement' ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : ''}`}
                  value={formData.department_id}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      department_id: Number(e.target.value),
                    })
                  }
                  disabled={user.role === 'responsable-departement'}
                >
                  {departments?.map((department) => (
                    <option key={department.id} value={department.id}>
                      {department.name}
                    </option>
                  ))}
                </select>
                {user.role === 'responsable-departement' && (
                  <Lock
                    size={14}
                    className="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                  />
                )}
              </div>
            </div>
            <div>
              <label htmlFor="teacher-color" className="block text-sm font-medium text-gray-700 mb-1">
                {t('colorTag')}
              </label>
              <div className="flex items-center space-x-2">
                <input
                  id="teacher-color"
                  type="color"
                  className="h-9 w-full rounded cursor-pointer border border-gray-300"
                  value={formData.color}
                  onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                />
              </div>
            </div>
          </div>
          <div>
            <label htmlFor="teacher-speciality" className="block text-sm font-medium text-gray-700 mb-1">
              {t('speciality')}
            </label>
            <input
              id="teacher-speciality"
              required
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.speciality}
              onChange={(e) => setFormData({ ...formData, speciality: e.target.value })}
            />
          </div>
          {/* <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t("address")}
            </label>
            <input
              type="text"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.address}
              onChange={(e) =>
                setFormData({ ...formData, address: e.target.value })
              }
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t("bio")}
            </label>
            <textarea
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              rows={3}
              value={formData.bio}
              onChange={(e) =>
                setFormData({ ...formData, bio: e.target.value })
              }
            />
          </div> */}

          <div className="pt-4 flex justify-end space-x-3">
            <button
              type="button"
              onClick={() => setIsModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg font-medium transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="submit"
              className="px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary font-medium transition-colors shadow-sm"
            >
              {editingTeacher ? t('save') : t('add')}
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
          <p className="text-gray-700 mb-6">{t('confirmDeleteTeacher')}</p>
          <div className="flex justify-end space-x-2">
            <button
              type="button"
              onClick={() => setIsDeleteModalOpen(false)}
              className="px-4 py-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            >
              {t('cancel')}
            </button>
            <button
              type="button"
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
