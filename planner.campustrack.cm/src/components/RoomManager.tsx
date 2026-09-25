import { SmartPagination } from '@/src/components/SmartPagination';
import React, { useState } from 'react';
import { Room } from '@/src/lib/types';
import { Modal } from './Modal';
import { Plus, Edit2, Trash2, Search, Building, Users, MapPin } from 'lucide-react';
import { useTranslation } from '@/src/utils/i18n';
interface RoomManagerProps {
  rooms: Room[];
  currentPage?: number;
  totalPages?: number;
  onPageChange?: (page: number) => void;
  onAddRoom: (room: Room) => void;
  onUpdateRoom: (room: Room) => void;
  onDeleteRoom: (id: number) => void;
}

export const RoomManager: React.FC<RoomManagerProps> = ({
  rooms,
  currentPage = 1,
  totalPages = 1,
  onPageChange,
  onAddRoom,
  onUpdateRoom,
  onDeleteRoom,
}) => {
  const { t } = useTranslation();
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [editingRoom, setEditingRoom] = useState<Room | null>(null);

  const [formData, setFormData] = useState<Partial<Room>>({
    name: '',
    code: '',
    capacity: 30,
    type: 'classroom',
    building: 'Main Block',
  });

  const filteredRooms = rooms?.filter(
    (room) =>
      room.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
      (room.building ?? '').toLowerCase().includes(searchTerm.toLowerCase()) ||
      room.type.toLowerCase().includes(searchTerm.toLowerCase()),
  );

  const handleOpenModal = (room?: Room) => {
    if (room) {
      setEditingRoom(room);
      setFormData(room);
    } else {
      setEditingRoom(null);
      setFormData({
        name: '',
        capacity: 30,
        type: 'classroom',
        building: 'Main Block',
      });
    }
    setIsModalOpen(true);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name || !formData.code || !formData.building) return;

    if (editingRoom) {
      onUpdateRoom({ ...editingRoom, ...formData } as Room);
    } else {
      onAddRoom({
        // id: formatDateTime(new Date().toISOString().split("T")[0]),
        ...formData,
      } as Room);
    }
    setIsModalOpen(false);
  };

  return (
    <div className="space-y-6 h-full flex flex-col">
      <div className="flex flex-col sm:flex-row justify-between items-center gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div className="relative w-full sm:w-96">
          <Search
            className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"
            size={20}
          />
          <input
            type="text"
            placeholder={t('searchRooms')}
            className="w-full pl-10 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none transition-all"
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
          />
        </div>
        <button
          onClick={() => handleOpenModal()}
          className="flex items-center justify-center space-x-2 px-4 py-2 bg-primary text-white rounded-lg hover:bg-secondary transition-colors w-full sm:w-auto font-medium"
        >
          <Plus size={20} />
          <span>{t('addRoom')}</span>
        </button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 overflow-y-auto pb-4">
        {filteredRooms?.map((room) => (
          <div
            key={room.id}
            className="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-all group flex flex-col"
          >
            <div className="p-6 flex-1">
              <div className="flex justify-between items-start mb-4">
                <div className="p-3 bg-orange-50 text-orange-600 rounded-lg">
                  <Building size={24} />
                </div>
                <div className="flex space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                  <button
                    onClick={() => handleOpenModal(room)}
                    className="p-2 text-gray-500 hover:text-secondary hover:bg-indigo-50 rounded-full"
                  >
                    <Edit2 size={16} />
                  </button>
                  <button
                    onClick={() => onDeleteRoom(room.id)}
                    className="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-full"
                  >
                    <Trash2 size={16} />
                  </button>
                </div>
              </div>

              <h3 className="text-lg font-bold text-gray-900 mb-1">{room.name}</h3>
              <p className="text-sm text-gray-500 font-medium mb-4">{room.type}</p>

              <div className="space-y-2 border-t border-gray-50 pt-4">
                <div className="flex items-center justify-between text-sm">
                  <div className="flex items-center text-gray-600">
                    <MapPin size={16} className="mr-2 text-gray-400" />
                    {room.building}
                  </div>
                </div>
                <div className="flex items-center justify-between text-sm">
                  <div className="flex items-center text-gray-600">
                    <Users size={16} className="mr-2 text-gray-400" />
                    {t('capacity')}: {room.capacity}
                  </div>
                </div>
              </div>
            </div>
            <div className="h-1 w-full bg-orange-400 rounded-b-xl opacity-50" />
          </div>
        ))}
        {filteredRooms?.length === 0 && (
          <div className="col-span-full flex flex-col items-center justify-center py-12 text-gray-400">
            <Building size={48} className="mb-4 opacity-20" />
            <p>No rooms found matching your criteria.</p>
          </div>
        )}

        <SmartPagination
          currentPage={currentPage}
          totalPages={totalPages}
          onPageChange={onPageChange}
        />
      </div>

      <Modal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        title={editingRoom ? t('editRoom') : t('newRoom')}
      >
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('roomName')}</label>
            <input
              required
              type="text"
              placeholder="e.g. Hall 101"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.name}
              onChange={(e) => setFormData({ ...formData, name: e.target.value })}
            />
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('roomCode')}</label>
            <input
              required
              type="text"
              placeholder="e.g. H101"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.code}
              onChange={(e) => setFormData({ ...formData, code: e.target.value })}
            />
          </div>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                {t('capacity')}
              </label>
              <input
                required
                type="number"
                min="1"
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.capacity}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    capacity: parseInt(e.target.value) || 0,
                  })
                }
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">{t('type')}</label>
              <select
                className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
                value={formData.type}
                onChange={(e) =>
                  setFormData({
                    ...formData,
                    type: e.target.value as Room['type'],
                  })
                }
              >
                <option value="classroom">Classroom</option>
                <option value="amphitheater">Lecture Hall</option>
                <option value="lab">Computer Lab</option>
                <option value="lab">Laboratory</option>
                <option value="study_room">Seminar Room</option>
                <option value="conference">Auditorium</option>
              </select>
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{t('building')}</label>
            <input
              required
              type="text"
              placeholder="e.g. Main Block"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 outline-none"
              value={formData.building}
              onChange={(e) => setFormData({ ...formData, building: e.target.value })}
            />
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
              {editingRoom ? t('save') : t('add')}
            </button>
          </div>
        </form>
      </Modal>
    </div>
  );
};
