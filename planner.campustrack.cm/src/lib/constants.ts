import { DayOfWeek, TimeSlot } from '@/src/lib/types';

export const API_BASE_URL = (import.meta as any).env.VITE_API_BASE_URL;

export const DEPARTMENTS = ['Computer Science', 'Mathematics', 'Physics', 'Literature', 'Arts'];

export const SUBJECTS = [
  'Intro to CS',
  'Adv. Algorithms',
  'Physics I',
  'Modern Poetry',
  'Database Systems',
  'Web Development',
  'Linear Algebra',
  'Calculus I',
  'Calculus II',
  'Quantum Mechanics',
  'Creative Writing',
  'Art History',
  'Graphic Design',
  'Macroeconomics',
  'Business Ethics',
  'Software Engineering',
  'Machine Learning',
];

export const TIME_SLOTS_ARRAY = Object.values(TimeSlot).filter((t) => t !== TimeSlot.Lunch);
export const DAYS_ARRAY = Object.values(DayOfWeek);

export const TIME_SLOTS = [
  { start: '08:00', end: '09:00', label: '08:00 - 09:00' },
  { start: '09:00', end: '10:00', label: '09:00 - 10:00' },
  { start: '10:00', end: '11:00', label: '10:00 - 11:00' },
  { start: '11:00', end: '12:00', label: '11:00 - 12:00' },
  { start: '13:00', end: '14:00', label: '13:00 - 14:00' },
  { start: '14:00', end: '15:00', label: '14:00 - 15:00' },
  { start: '15:00', end: '16:00', label: '15:00 - 16:00' },
  { start: '16:00', end: '17:00', label: '16:00 - 17:00' },
];
