import { createFileRoute } from '@tanstack/react-router';
import { RoomManager } from '@/src/components/RoomManager';
import { useRooms, useCreateRoom, useUpdateRoom, useDeleteRoom } from '@/src/hooks/useRooms';
import { useAuth } from '@/src/auth';
import { hasAnyPermission } from '@/src/utils/routePermissions';
import { Room } from '@/src/lib/types';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { ErrorState } from '@/src/components/ErrorState';
import { useState } from 'react';
import { useDebouncedValue } from '@/src/hooks/useDebouncedValue';

export const Route = createFileRoute('/rooms')({
  component: RoomsPage,
});

function RoomsPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebouncedValue(search);
  const { user } = useAuth();
  const readOnly = !hasAnyPermission(user?.permissions, ['rooms.manage']);
  const { data: rooms, isLoading, isError, refetch } = useRooms({
    page,
    search: debouncedSearch || undefined,
  });
  const createRoom = useCreateRoom();
  const updateRoom = useUpdateRoom();
  const deleteRoom = useDeleteRoom();

  const handleAddRoom = (room: Omit<Room, 'id'>) => {
    createRoom.mutate({
      name: room.name,
      code: room.code,
      type: room.type,
      capacity: room.capacity,
      department_id: room.department_id,
      floor: room.floor,
      building: room.building,
      has_projector: room.has_projector,
      has_computers: room.has_computers,
      has_whiteboard: room.has_whiteboard,
      is_active: room.is_active ?? true,
      description: room.description,
    });
  };

  const handleUpdateRoom = (room: Room) => {
    updateRoom.mutate({
      id: room.id,
      data: {
        name: room.name,
        code: room.code,
        type: room.type,
        capacity: room.capacity,
        department_id: room.department_id,
        floor: room.floor,
        building: room.building,
        has_projector: room.has_projector,
        has_computers: room.has_computers,
        has_whiteboard: room.has_whiteboard,
        is_active: room.is_active,
        description: room.description,
      },
    });
  };

  const handleDeleteRoom = (id: number) => {
    deleteRoom.mutate(id);
  };

  const handleSearchChange = (value: string) => {
    setSearch(value);
    setPage(1);
  };

  if (isLoading) {
    return <LoadingSpinner />;
  }

  if (isError) {
    return <ErrorState onRetry={() => refetch()} />;
  }

  return (
    <RoomManager
      rooms={rooms?.data ?? []}
      currentPage={rooms?.current_page || 1}
      totalPages={rooms?.last_page || 1}
      onPageChange={setPage}
      readOnly={readOnly}
      searchTerm={search}
      onSearchChange={handleSearchChange}
      onAddRoom={handleAddRoom}
      onUpdateRoom={handleUpdateRoom}
      onDeleteRoom={handleDeleteRoom}
    />
  );
}
