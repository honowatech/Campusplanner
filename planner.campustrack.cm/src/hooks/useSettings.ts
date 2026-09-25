import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { settingsService, UpdateSettingsData } from '@/src/services/settingsService';
import { AppSettings } from '@/src/lib/types';
import { toast } from 'sonner';
import { getErrorMessage } from '@/src/services/api';
import type { AxiosError } from 'axios';

const DEFAULT_SETTINGS: AppSettings = {
  maxWeeklyHoursPerTeacher: 18,
  maxConsecutiveHours: 4,
  maxDailyHoursPerTeacher: 8,
  maxDailyHoursPerClass: 8,
  enableRoomConflict: true,
  enableTeacherConflict: true,
  enableGroupConflict: true,
  smsConfig: {
    apiKey: '',
    sender_id: 'CAMPUS PLANNER',
    balance: 0,
  },
};

export function useSettings() {
  return useQuery<AppSettings>({
    queryKey: ['settings'],
    queryFn: settingsService.get,
    staleTime: Infinity,
    initialData: DEFAULT_SETTINGS,
  });
}

export function useUpdateSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: UpdateSettingsData) => settingsService.update(data),
    onSuccess: (newSettings) => {
      queryClient.setQueryData(['settings'], newSettings);
      toast.success('Settings updated successfully!');
    },
    onError: (error) => {
      toast.error(getErrorMessage(error as unknown as AxiosError));
    },
  });
}
