export const formatDateTime = (
  datetime: string,
  options: {
    weekday: 'long' | 'short' | 'narrow';
    day: 'numeric' | '2-digit';
    month: 'long' | 'short' | 'narrow' | 'numeric' | '2-digit';
    year: 'numeric' | '2-digit';
  } = {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  },
) => {
  return new Date(datetime).toLocaleDateString('en-GB', {
    weekday: options.weekday,
    day: options.day,
    month: options.month,
    year: options.year,
  });
};
