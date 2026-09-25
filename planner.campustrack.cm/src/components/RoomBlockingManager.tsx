import React, { useState } from 'react';
import { RoomBlocking, Room } from '@/src/lib/types';
import { Modal } from './Modal';
import {
  Plus,
  Edit2,
  Trash2,
  Search,
  Calendar,
  AlertTriangle,
  Wrench,
  PartyPopper,
  Plane,
  HelpCircle,
  Clock,
} from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
import { formatDateTime } from '@/src/lib/helpers';

interface RoomBlockingManagerProps {
  blockings: RoomBlocking[];
  rooms: Room[];
  onAdd: (b: RoomBlocking) => void;
  onUpdate: (b: RoomBlocking) => void;
  onDelete: (id: number) => void;
}

const blockingTypeConfig = {
  maintenance: {
    icon: Wrench,
    color: 'text-orange-500 bg-orange-50',
    label: 'Maintenance',
  },
  event: {
    icon: PartyPopper,
    color: 'text-purple-500 bg-purple-50',
    label: 'Event',
  },
  holiday: { icon: Plane, color: 'text-blue-500 bg-blue-50', label: 'Holiday' },
  other: {
    icon: HelpCircle,
    color: 'text-gray-500 bg-gray-50',
    label: 'Other',
  },
};

export const RoomBlockingManager: React.FC<RoomBlockingManagerProps> = ({
  blockings,
  rooms,
  onAdd,
  onUpdate,
  onDelete,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [filterRoom, setFilterRoom] = useState<string>('');
  const [editingId, setEditingId] = useState<number | null>(null);

  const [formData, setFormData] = useState<Partial<RoomBlocking>>({
    room_id: undefined,
    start_datetime: '',
    end_datetime: '',
    reason: '',
    blocking_type: 'maintenance',
    is_recurring: false,
  });

  const filteredBlockings = blockings.filter((b) => {
    const room = rooms.find((r) => r.id === b.room_id);
    const matchesSearch =
      b.reason.toLowerCase().includes(searchTerm.toLowerCase()) ||
      room?.name.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesRoom = !filterRoom || b.room_id === Number(filterRoom);
    return matchesSearch && matchesRoom;
  });

  const getRoomName = (roomId: number) => {
    return rooms.find((r) => r.id === roomId)?.name || '-';
  };

  const handleOpenModal = (blocking?: RoomBlocking) => {
    if (blocking) {
      setEditingId(blocking.id);
      setFormData(blocking);
    } else {
      setEditingId(null);
      setFormData({
        room_id: rooms.length > 0 ? Number(rooms[0].id) : 0,
        start_datetime: '',
        end_datetime: '',
        reason: '',
        blocking_type: 'maintenance',
        is_recurring: false,
      });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.room_id || !formData.start_datetime || !formData.end_datetime || !formData.reason)
      return;

    if (editingId) {
      onUpdate({ id: editingId, ...formData } as RoomBlocking);
    } else {
      onAdd({
        id: formatDateTime(new Date().toISOString().split('T')[0]),
        ...formData,
      } as RoomBlocking);
    }
    setIsModalOpen(false);
  };

  return (
    <div className="space-y-6 h-full flex flex-col">
      <div className="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div className="flex flex-col sm:flex-row gap-3 w-full sm:w-auto flex-1">
          <div className="relative w-full sm:w-64">
            <Search
              className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
              size={20}
            />
            <input
              type="text"
              placeholder={t('searchBlockings')}
              className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>

          <div className="relative w-full sm:w-48">
            <select
              className="w-full px-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none appearance-none bg-white"
              value={filterRoom}
              onChange={(e) => setFilterRoom(e.target.value)}
            >
              <option value="">{t('allRooms')}</option>
              {rooms.map((r) => (
                <option key={r.id} value={r.id}>
                  {r.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        <button
          onClick={() => handleOpenModal()}
          className="flex items-center justify-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors w-full sm:w-auto font-medium"
        >
          <Plus size={20} />
          <span>{t('addBlocking')}</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 overflow-y-auto pb-4">
        {filteredBlockings.map((blocking) => {
          const config = blockingTypeConfig[blocking.blocking_type];
          const Icon = config.icon;

          return (
            <div
              key={blocking.id}
              className="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all group"
            >
              <div className="p-4">
                <div className="flex justify-between items-start mb-3">
                  <div className={`p-2 rounded-lg ${config.color}`}>
                    <Icon size={20} />
                  </div>
                  <div className="flex space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <button
                      onClick={() => handleOpenModal(blocking)}
                      className="p-1.5 text-gray-400 hover:text-secondary hover:bg-indigo-50 rounded-lg"
                    >
                      <Edit2 size={14} />
                    </button>
                    <button
                      onClick={() => onDelete(blocking.id)}
                      className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg"
                    >
                      <Trash2 size={14} />
                    </button>
                  </div>
                </div>

                <h3 className="font-semibold text-gray-900 mb-1">
                  {getRoomName(blocking.room_id)}
                </h3>
                <p className="text-sm text-gray-600 mb-3">{blocking.reason}</p>

                <div className="flex items-center justify-between text-xs">
                  <span className={`px-2 py-0.5 rounded-full ${config.color}`}>
                    {t(blocking.blocking_type)}
                  </span>
                  {blocking.is_recurring && (
                    <span className="text-gray-400 flex items-center">
                      <Clock size={12} className="mr-1" />
                      {t('recurring')}
                    </span>
                  )}
                </div>

                <div className="mt-3 pt-3 border-t border-gray-50 text-xs text-gray-500">
                  <div className="flex items-center mb-1">
                    <Calendar size={12} className="mr-1.5" />
                    {formatDateTime(blocking.start_datetime)}
                  </div>
                  <div className="flex items-center">
                    <Calendar size={12} className="mr-1.5" />
                    {formatDateTime(blocking.end_datetime)}
                  </div>
                </div>
              </div>
            </div>
          );
        })}

        {filteredBlockings.length === 0 && (
          <div className="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
            <AlertTriangle size={48} className="mb-4 opacity-20" />
            <p>{t('noBlockingsFound')}</p>
          </div>
        )}
      </div>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingId ? t('editBlocking') : t('newBlocking')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('room')}</label>
            <select
              required
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.room_id}
              onChange={(e) => setFormData({ ...formData, room_id: Number(e.target.value) })}
            >
              <option value={0} disabled>
                {t('selectRoom')}
              </option>
              {rooms.map((r) => (
                <option key={r.id} value={r.id}>
                  {r.name}
                </option>
              ))}
            </select>
          </div>

          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('startDateTime')}
              </label>
              <input
                required
                type="datetime-local"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.start_datetime}
                onChange={(e) => setFormData({ ...formData, start_datetime: e.target.value })}
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('endDateTime')}
              </label>
              <input
                required
                type="datetime-local"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.end_datetime}
                onChange={(e) => setFormData({ ...formData, end_datetime: e.target.value })}
              />
            </div>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
              {t('blockingType')}
            </label>
            <select
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.blocking_type}
              onChange={(e) =>
                setFormData({
                  ...formData,
                  blocking_type: e.target.value as RoomBlocking['blocking_type'],
                })
              }
            >
              <option value="maintenance">{t('maintenance')}</option>
              <option value="event">{t('event')}</option>
              <option value="holiday">{t('holiday')}</option>
              <option value="other">{t('other')}</option>
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('reason')}</label>
            <textarea
              required
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              rows={2}
              value={formData.reason}
              onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
            />
          </div>

          <div className="flex items-center">
            <input
              type="checkbox"
              id="recurring"
              className="h-4 w-4 text-secondary focus:ring-indigo-500 border-gray-300 rounded"
              checked={formData.is_recurring}
              onChange={(e) => setFormData({ ...formData, is_recurring: e.target.checked })}
            />
            <label htmlFor="recurring" className="ml-2 text-sm text-gray-700">
              {t('isRecurring')}
            </label>
          </div>

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
              {editingId ? t('save') : t('add')}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
