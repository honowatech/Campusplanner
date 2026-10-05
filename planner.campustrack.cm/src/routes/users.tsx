import { createFileRoute } from '@tanstack/react-router';
import { UserManager } from '@/src/components/UserManager';
import {
  useUsers,
  useCreateUser,
  useUpdateUser,
  useDeleteUser,
  usePendingUsers,
  useApproveUser,
  useRejectUser,
  useDeactivateUser,
} from '@/src/hooks/useUsers';
import { useDepartments } from '@/src/hooks/useDepartments';
import { User } from '@/src/lib/types';
import type { CreateUserData } from '@/src/services/userService';
import { useState } from 'react';
import { LoadingSpinner } from '@/src/components/LoadingSpinner';
import { ErrorState } from '@/src/components/ErrorState';
import { useDebouncedValue } from '@/src/hooks/useDebouncedValue';

export const Route = createFileRoute('/users')({
  component: UsersPage,
});

function UsersPage() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const debouncedSearch = useDebouncedValue(search);
  const { data: appUsers, isLoading: usersLoading, isError: usersError, refetch: refetchUsers } = useUsers({
    page,
    search: debouncedSearch || undefined,
  });
  const { data: departments, isLoading: departmentsLoading } = useDepartments();

  const createUser = useCreateUser();
  const updateUser = useUpdateUser();
  const deleteUser = useDeleteUser();
  const { data: pendingData } = usePendingUsers();
  const approveUser = useApproveUser();
  const rejectUser = useRejectUser();
  const deactivateUser = useDeactivateUser();

  const handleAdd = (user: CreateUserData) => {
    createUser.mutate(user);
  };

  const handleUpdate = (user: User) => {
    updateUser.mutate({
      id: user.id,
      data: {
        name: user.name,
        email: user.email,
        department_id: user.department_id,
      },
    });
  };

  const handleDelete = (id: number) => {
    deleteUser.mutate(id);
  };

  const handleApprove = (id: number, role?: string) => {
    approveUser.mutate({ id, role });
  };

  const handleRejectAccount = (id: number) => {
    rejectUser.mutate(id);
  };

  const handleDeactivate = (id: number) => {
    deactivateUser.mutate(id);
  };

  const handleSearchChange = (value: string) => {
    setSearch(value);
    setPage(1);
  };

  if (usersLoading || departmentsLoading) {
    return <LoadingSpinner />;
  }

  if (usersError) {
    return <ErrorState onRetry={() => refetchUsers()} />;
  }

  return (
    <UserManager
      users={appUsers?.data ?? []}
      departments={departments?.data ?? []}
      currentPage={appUsers?.current_page || 1}
      totalPages={appUsers?.last_page || 1}
      onPageChange={setPage}
      searchTerm={search}
      onSearchChange={handleSearchChange}
      onAdd={handleAdd}
      onUpdate={handleUpdate}
      onDelete={handleDelete}
      pendingUsers={pendingData?.data ?? []}
      onApprove={handleApprove}
      onReject={handleRejectAccount}
      onDeactivate={handleDeactivate}
    />
  );
}
