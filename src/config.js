import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders'; // استدعاء الـ loader الجديد

const postsCollection = defineCollection({
  // تحديد مصدر الملفات محلياً باستخدام glob
  loader: glob({ pattern: "**/*.{md,mdx}", base: "./src/content/posts" }),
  schema: z.object({
    title: z.string(),
    date: z.string(),
    description: z.string(),
    category: z.string().optional(),
  }),
});

export const collections = {
  'posts': postsCollection,
};