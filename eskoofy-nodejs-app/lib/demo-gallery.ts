/**
 * Demo gallery mirror — exact rows from eskoofy-laravel-app/database/seeders/DemoGallerySeeder.php.
 */
export interface DemoGalleryRow {
  title: string;
  description: string;
  category: string;
  is_published: boolean;
}

export const demoGallery: readonly DemoGalleryRow[] = [
  { title: "Annual Sports Day 2025", description: "Highlights from our annual sports competition", category: "sports", is_published: true },
  { title: "Cultural Program", description: "Students performing at the cultural event", category: "cultural", is_published: true },
  { title: "Science Fair Exhibition", description: "Innovative projects by our young scientists", category: "academic", is_published: true },
  { title: "Independence Day Celebration", description: "Patriotic programs and parade", category: "cultural", is_published: true },
  { title: "Classroom Activities", description: "Daily learning moments captured", category: "academic", is_published: true },
  { title: "Field Trip 2025", description: "Educational tour to the National Museum", category: "academic", is_published: true },
  { title: "Graduation Ceremony", description: "Class of 2025 graduation day", category: "cultural", is_published: true },
];
