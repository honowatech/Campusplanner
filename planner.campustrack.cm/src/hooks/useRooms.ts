import { createCrudHooks } from '@/src/hooks/createCrudHooks';
import {
  roomService,
  RoomParams,
  CreateRoomData,
  UpdateRoomData,
} from '@/src/services/roomService';
import { Room } from '@/src/lib/types';

const crud = createCrudHooks<Room, RoomParams, CreateRoomData, UpdateRoomData>({
  service: roomService,
  queryKey: 'rooms',
  singleKey: 'room',
  label: 'Salle',
});

export const useRooms = crud.useList;
export const useRoom = crud.useGet;
export const useCreateRoom = crud.useCreate;
export const useUpdateRoom = crud.useUpdate;
export const useDeleteRoom = crud.useDelete;
