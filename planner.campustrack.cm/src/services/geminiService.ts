import { apiClient } from '@/src/services/api';
import { Teacher, Course } from '../lib/types';

// Le service IA est proxifié par l'API Laravel (/api/ai/generate) :
// la clé Gemini ne quitte jamais le serveur.

type AiGenerateResponse = { text: string };

const generate = async (payload: {
  prompt: string;
  system_instruction?: string;
  response_json?: boolean;
}): Promise<string> => {
  const res = await apiClient.post<{ data: AiGenerateResponse }>('/api/ai/generate', payload);
  return res.data?.data?.text ?? '';
};

export const generateWorkloadAnalysis = async (
  teachers: Teacher[],
  courses: Course[],
): Promise<string> => {
  try {
    const dataContext = JSON.stringify({
      teachers: teachers.map((teacher) => ({
        full_name: teacher.full_name,
        dept: teacher.department,
      })),
      schedule: courses.map((course) => ({
        course: course.name,
        teacher: teachers.find((teacher) => teacher.id === course.teacher_id)?.full_name,
      })),
    });

    const prompt = `
      Analyze the following university schedule data and provide a brief, professional executive summary (max 3 paragraphs).
      Focus on workload distribution among departments, potential scheduling bottlenecks, and suggestions for optimization.
      Data: ${dataContext}
    `;

    const text = await generate({
      prompt,
      system_instruction: 'You are an expert university registrar consultant.',
    });

    return text || 'No analysis could be generated.';
  } catch (error) {
    console.error('AI analysis error:', error);
    return 'Unable to generate AI analysis at this time.';
  }
};

export const suggestScheduleOptimization = async (
  teachers: Teacher[],
  courses: Course[],
): Promise<{ suggestions: string[] }> => {
  try {
    const prompt = `Given the current schedule, suggest 3 specific optimizations to improve room utilization or teacher balance. Return ONLY valid JSON.`;

    const text = await generate({
      prompt: `
        Context: ${JSON.stringify(courses)}
        Task: ${prompt}
      `,
      response_json: true,
    });

    if (!text) return { suggestions: [] };
    return JSON.parse(text);
  } catch (e) {
    console.error(e);
    return { suggestions: ['Error generating suggestions.'] };
  }
};
