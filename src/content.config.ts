import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const blog = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "src/content/blog" }),
  schema: z.object({
    title: z.string(),
    // قبول التاريخ أيا كان نوعه أو تجاهله إذا تسبب بمشكلة
    date: z.any().optional(),
    description: z.string().optional(),
    category: z.string().optional(),
    image: z.string().optional(),
  }),
});

export const collections = { blog };