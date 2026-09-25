import { useState, useCallback } from 'react';
import { ZodSchema } from 'zod';

type ValidationErrors<T> = Partial<Record<keyof T, string>>;

interface UseFormValidationReturn<T> {
  errors: ValidationErrors<T>;
  validate: (data: unknown) => { success: boolean; data?: T; errors?: ValidationErrors<T> };
  clearError: (field: keyof T) => void;
  clearAllErrors: () => void;
}

export function useFormValidation<T extends Record<string, unknown>>(
  schema: ZodSchema<T>,
): UseFormValidationReturn<T> {
  const [errors, setErrors] = useState<ValidationErrors<T>>({});

  const validate = useCallback(
    (data: unknown): { success: boolean; data?: T; errors?: ValidationErrors<T> } => {
      const result = schema.safeParse(data);

      if (result.success) {
        setErrors({});
        return { success: true, data: result.data };
      } else {
        const formattedErrors: ValidationErrors<T> = {};
        result.error.issues.forEach((issue) => {
          const path = issue.path[0] as keyof T;
          formattedErrors[path] = issue.message;
        });
        setErrors(formattedErrors);
        return { success: false, errors: formattedErrors };
      }
    },
    [schema],
  );

  const clearError = useCallback((field: keyof T) => {
    setErrors((prev) => ({ ...prev, [field]: undefined }));
  }, []);

  const clearAllErrors = useCallback(() => {
    setErrors({});
  }, []);

  return { errors, validate, clearError, clearAllErrors };
}
