import { z } from 'zod';

export const loginSchema = z.object({
  email: z.string().min(1, "L'email est requis").email("Format d'email invalide"),
  password: z
    .string()
    .min(1, 'Le mot de passe est requis')
    .min(6, 'Le mot de passe doit contenir au moins 6 caractères'),
});

// Rôles attribuables à l'inscription self-service (jamais les rôles admin)
export const REGISTRABLE_ROLES = [
  'professeur',
  'etudiant',
  'personnel-administratif',
  'responsable-departement',
] as const;

export type RegistrableRole = (typeof REGISTRABLE_ROLES)[number];

export const registerSchema = z
  .object({
    name: z
      .string()
      .min(1, 'Le nom est requis')
      .min(2, 'Le nom doit contenir au moins 2 caractères'),
    email: z.string().min(1, "L'email est requis").email("Format d'email invalide"),
    password: z
      .string()
      .min(1, 'Le mot de passe est requis')
      .min(8, 'Le mot de passe doit contenir au moins 8 caractères'),
    password_confirmation: z.string().min(1, 'La confirmation du mot de passe est requise'),
    requested_role: z.enum(REGISTRABLE_ROLES, {
      error: 'Veuillez choisir un rôle',
    }),
    department_id: z.number().optional(),
  })
  .refine((data) => data.password === data.password_confirmation, {
    message: 'Les mots de passe ne correspondent pas',
    path: ['password_confirmation'],
  });

export type LoginFormData = z.infer<typeof loginSchema>;
export type RegisterFormData = z.infer<typeof registerSchema>;

// Inscription libre-service d'une école (tenant + compte administrateur)
export const schoolSignupSchema = z
  .object({
    name: z.string().min(2, "Le nom de l'établissement est requis"),
    admin_name: z.string().min(2, 'Votre nom est requis'),
    admin_email: z.string().min(1, "L'email est requis").email("Format d'email invalide"),
    phone: z
      .string()
      .regex(/^[+\d\s().-]{9,20}$/, 'Numéro de téléphone invalide')
      .optional()
      .or(z.literal('')),
    admin_password: z.string().min(8, 'Le mot de passe doit contenir au moins 8 caractères'),
    admin_password_confirmation: z.string().min(1, 'La confirmation du mot de passe est requise'),
  })
  .refine((data) => data.admin_password === data.admin_password_confirmation, {
    message: 'Les mots de passe ne correspondent pas',
    path: ['admin_password_confirmation'],
  });

export type SchoolSignupFormData = z.infer<typeof schoolSignupSchema>;
