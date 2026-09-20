/**
 * Demo events mirror — exact rows from eskoofy-laravel-app/database/seeders/DemoEventSeeder.php.
 */
export interface DemoEventRow {
  title: string;
  description: string;
  location: string;
  status: string;
}

export const demoEvents: readonly DemoEventRow[] = [
  { title: "Annual Sports Day", description: "Annual sports competition with athletics, races, and team games.", location: "School Playground", status: "published" },
  { title: "Cultural Program 2025", description: "Students showcase their talents in music, dance, and drama.", location: "School Auditorium", status: "published" },
  { title: "Parent-Teacher Meeting", description: "Quarterly meeting to discuss student progress and development.", location: "School Hall", status: "published" },
  { title: "Science Fair", description: "Students present their science projects and innovations.", location: "Science Building", status: "published" },
  { title: "Independence Day Celebration", description: "Celebrating Bangladesh Independence Day with parade and cultural events.", location: "School Field", status: "published" },
  { title: "Victory Day Program", description: "Celebrating Victory Day with special assembly and performances.", location: "School Field", status: "published" },
  { title: "Educational Tour", description: "Annual educational tour for senior students.", location: "", status: "published" },
];
