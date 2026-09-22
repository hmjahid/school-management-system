import { locale } from "@/lib/i18n";

/**
 * Help & Documentation content — ported from `eskoofy-laravel-app/lang/{en,bn}
 * /dashboard.php` (`dashboard.help.sections`). Kept as typed data (rather than
 * flat `lang/*.ts` keys) to preserve the ordered section → steps structure.
 */

export interface HelpSection {
  key: string;
  title: string;
  description: string;
  steps: string[];
  color: "brand" | "emerald" | "amber" | "violet" | "teal" | "rose" | "cyan";
}

export interface HelpStrings {
  pageDescription: string;
  searchPlaceholder: string;
  noResults: string;
  wasThisHelpful: string;
  yes: string;
  no: string;
}

const COLORS: Record<NonNullable<HelpSection["color"]>, { bg: string; text: string; icon: string }> = {
  brand: {
    bg: "bg-brand-100 dark:bg-brand-900/40",
    text: "text-brand-600 dark:text-brand-400",
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>',
  },
  emerald: {
    bg: "bg-emerald-100 dark:bg-emerald-900/40",
    text: "text-emerald-600 dark:text-emerald-400",
    icon: '<path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838L7.667 9.088l1.94.831a1 1 0 00.787 0l7-3a1 1 0 000-1.838l-7-3z"/>',
  },
  amber: {
    bg: "bg-amber-100 dark:bg-amber-900/40",
    text: "text-amber-600 dark:text-amber-400",
    icon: '<path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>',
  },
  violet: {
    bg: "bg-violet-100 dark:bg-violet-900/40",
    text: "text-violet-600 dark:text-violet-400",
    icon: '<path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd"/>',
  },
  teal: {
    bg: "bg-teal-100 dark:bg-teal-900/40",
    text: "text-teal-600 dark:text-teal-400",
    icon: '<path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000-16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>',
  },
  rose: {
    bg: "bg-rose-100 dark:bg-rose-900/40",
    text: "text-rose-600 dark:text-rose-400",
    icon: '<path fill-rule="evenodd" d="M4.083 9h1.946c.089-1.546.383-2.97.837-4.118A6.004 6.004 0 004.083 9zM10 2a8 8 0 100 16 8 8 0 000-16zm0 2c-.076 0-.232.032-.465.262-.238.234-.497.623-.737 1.182-.389.907-.673 2.142-.766 3.556h3.936c-.093-1.414-.377-2.649-.766-3.556-.24-.56-.5-.948-.737-1.182C10.232 4.032 10.076 4 10 4zm3.971 5c-.089-1.546-.383-2.97-.837-4.118A6.004 6.004 0 0115.917 9h-1.946zm-2.003 2H8.032c.093 1.414.377 2.649.766 3.556.24.56.5.948.737 1.182.233.23.389.262.465.262.076 0 .232-.032.465-.262.238-.234.498-.623.737-1.182.389-.907.673-2.142.766-3.556zm1.166 4.118c.454-1.147.748-2.572.837-4.118h1.946a6.004 6.004 0 01-2.783 4.118zm-6.268 0C6.412 13.97 6.118 12.546 6.03 11H4.083a6.004 6.004 0 002.783 4.118z" clip-rule="evenodd"/>',
  },
  cyan: {
    bg: "bg-cyan-100 dark:bg-cyan-900/40",
    text: "text-cyan-600 dark:text-cyan-400",
    icon: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>',
  },
};

const EN_SECTIONS: Array<Omit<HelpSection, "color"> & { color: HelpSection["color"] }> = [
  {
    key: "getting_started",
    color: "brand",
    title: "Getting Started",
    description: "Set up your school management system in a few simple steps.",
    steps: [
      "Log in to the admin panel using your credentials.",
      "Set up your school name, logo, and tagline in School Settings.",
      "Add classes, then create student and teacher accounts.",
      "Configure academic sessions and batches before managing attendance or exams.",
    ],
  },
  {
    key: "managing_students",
    color: "emerald",
    title: "Managing Students",
    description: "Register, update, and organize student records.",
    steps: [
      "Navigate to Academic > People > Students to view all students.",
      'Click "Add Student" to register a new student with class and batch.',
      "Edit or delete students from the student detail page.",
      "Use Bulk Import to add many students from a CSV file.",
    ],
  },
  {
    key: "managing_teachers",
    color: "brand",
    title: "Managing Teachers",
    description: "Create teacher accounts and manage faculty assignments.",
    steps: [
      "Go to Academic > People > Teachers to manage faculty.",
      "Create teacher accounts and assign them to subjects and classes.",
      "Teachers can be given specific roles for access control.",
      "Staff directory provides a complete list of all school staff.",
    ],
  },
  {
    key: "attendance",
    color: "amber",
    title: "Attendance",
    description: "Track daily student and staff attendance efficiently.",
    steps: [
      "Go to Academic > Daily > Attendance to mark daily attendance.",
      "Use Bulk Mark to record attendance for an entire class at once.",
      "Staff attendance tracks teacher and staff presence separately.",
      "Attendance reports are available under Reports.",
    ],
  },
  {
    key: "exams_results",
    color: "violet",
    title: "Exams & Results",
    description: "Create exams, enter marks, and publish results.",
    steps: [
      "Create exams under Academic > Academics > Exams.",
      "Enter marks per subject; results are computed automatically.",
      "Publish results so students and parents can view them.",
      "Export results as spreadsheets for offline use.",
    ],
  },
  {
    key: "fees_payments",
    color: "teal",
    title: "Fees & Payments",
    description: "Manage fee structures, collect payments, and track finances.",
    steps: [
      "Create fee categories and fee types under Academic > Finance.",
      "Record payments when students submit fees.",
      "Track expenses and manage the ledger for accounting.",
      "Generate income statements and balance sheets from Reports.",
    ],
  },
  {
    key: "cms_management",
    color: "rose",
    title: "CMS Management",
    description: "Manage website content, news, and public-facing pages.",
    steps: [
      "Edit static pages (About, Admissions, etc.) under Website CMS.",
      "Publish news, gallery items, and announcements.",
      "Manage documents and downloadable files.",
      "Review form submissions from the public website.",
    ],
  },
  {
    key: "public_website",
    color: "cyan",
    title: "Public Website",
    description: "Learn about the public-facing website and its features.",
    steps: [
      "The public site is built with Laravel Blade and Tailwind CSS.",
      "All content is managed from the dashboard CMS section.",
      "Multi-language support: English and Bengali are built in.",
      "Use the sitemap at /sitemap.xml for SEO indexing.",
    ],
  },
];

const BN_SECTIONS: Array<Omit<HelpSection, "color"> & { color: HelpSection["color"] }> = [
  {
    key: "getting_started",
    color: "brand",
    title: "শুরু করুন",
    description: "কয়েকটি সহজ ধাপে আপনার স্কুল ব্যবস্থাপনা সিস্টেম সেট করুন।",
    steps: [
      "আপনার প্রমাণপত্র ব্যবহার করে অ্যাডমিন প্যানেলে লগইন করুন।",
      "স্কুল সেটিংসে আপনার স্কুলের নাম, লোগো এবং ট্যাগলাইন সেট করুন।",
      "শ্রেণি যোগ করুন, তারপর শিক্ষার্থী এবং শিক্ষক অ্যাকাউন্ট তৈরি করুন।",
      "উপস্থিতি বা পরীক্ষা পরিচালনা করার আগে একাডেমিক সেশন এবং ব্যাচ কনফিগার করুন।",
    ],
  },
  {
    key: "managing_students",
    color: "emerald",
    title: "শিক্ষার্থী পরিচালনা",
    description: "শিক্ষার্থীদের রেকর্ড নিবন্ধন, আপডেট এবং সংগঠিত করুন।",
    steps: [
      "সকল শিক্ষার্থী দেখতে Academic > People > Students-এ যান।",
      'নতুন শিক্ষার্থীকে শ্রেণি ও ব্যাচ সহ নিবন্ধন করতে "Add Student" ক্লিক করুন।',
      "শিক্ষার্থীর বিস্তারিত পৃষ্ঠা থেকে শিক্ষার্থীদের সম্পাদনা বা মুছুন।",
      "অনেক শিক্ষার্থীকে CSV ফাইল থেকে যোগ করতে Bulk Import ব্যবহার করুন।",
    ],
  },
  {
    key: "managing_teachers",
    color: "brand",
    title: "শিক্ষক পরিচালনা",
    description: "শিক্ষক অ্যাকাউন্ট তৈরি করুন এবং শিক্ষক নিয়োগ পরিচালনা করুন।",
    steps: [
      "শিক্ষক পরিচালনা করতে Academic > People > Teachers-এ যান।",
      "শিক্ষক অ্যাকাউন্ট তৈরি করুন এবং তাদের বিষয় ও শ্রেণিতে নিয়োগ দিন।",
      "শিক্ষকদের অ্যাক্সেস নিয়ন্ত্রণের জন্য নির্দিষ্ট ভূমিকা দেওয়া যেতে পারে।",
      "কর্মী তালিকা সকল স্কুল কর্মীদের সম্পূর্ণ তালিকা প্রদান করে।",
    ],
  },
  {
    key: "attendance",
    color: "amber",
    title: "উপস্থিতি",
    description: "দৈনিক শিক্ষার্থী ও কর্মীদের উপস্থিতি দক্ষতার সাথে ট্র্যাক করুন।",
    steps: [
      "দৈনিক উপস্থিতি চিহ্নিত করতে Academic > Daily > Attendance-এ যান।",
      "পুরো শ্রেণির জন্য একসাথে উপস্থিতি রেকর্ড করতে Bulk Mark ব্যবহার করুন।",
      "কর্মীদের উপস্থিতি শিক্ষক ও কর্মীদের উপস্থিতি আলাদাভাবে ট্র্যাক করে।",
      "উপস্থিতি রিপোর্ট Reports-এ পাওয়া যায়।",
    ],
  },
  {
    key: "exams_results",
    color: "violet",
    title: "পরীক্ষা ও ফলাফল",
    description: "পরীক্ষা তৈরি করুন, নম্বর প্রবেশ করুন এবং ফলাফল প্রকাশ করুন।",
    steps: [
      "Academic > Academics > Exams-এ পরীক্ষা তৈরি করুন।",
      "বিষয় অনুযায়ী নম্বর প্রবেশ করুন; ফলাফল স্বয়ংক্রিয়ভাবে গণনা করা হয়।",
      "শিক্ষার্থী ও অভিভাবকদের ফলাফল দেখার জন্য প্রকাশ করুন।",
      "অফলাইন ব্যবহারের জন্য ফলাফল স্প্রেডশিট হিসাবে রপ্তানি করুন।",
    ],
  },
  {
    key: "fees_payments",
    color: "teal",
    title: "ফি ও পেমেন্ট",
    description: "ফি কাঠামো পরিচালনা করুন, পেমেন্ট সংগ্রহ করুন এবং আর্থিক বিষয় ট্র্যাক করুন।",
    steps: [
      "Academic > Finance-এ ফি বিভাগ এবং ফি ধরন তৈরি করুন।",
      "শিক্ষার্থীরা ফি জমা দেওয়ার সময় পেমেন্ট রেকর্ড করুন।",
      "হিসাবরক্ষণের জন্য ব্যয় এবং লেজার পরিচালনা করুন।",
      "Reports থেকে আয়ের বিবরণী এবং ব্যালেন্স শিট তৈরি করুন।",
    ],
  },
  {
    key: "cms_management",
    color: "rose",
    title: "সিএমএস ব্যবস্থাপনা",
    description: "ওয়েবসাইট কনটেন্ট, সংবাদ এবং পাবলিক পৃষ্ঠা পরিচালনা করুন।",
    steps: [
      "ওয়েবসাইট সিএমএস-এ স্ট্যাটিক পৃষ্ঠা (About, Admissions ইত্যাদি) সম্পাদনা করুন।",
      "সংবাদ, গ্যালারি আইটেম এবং ঘোষণা প্রকাশ করুন।",
      "ডকুমেন্ট এবং ডাউনলোডযোগ্য ফাইল পরিচালনা করুন।",
      "পাবলিক ওয়েবসাইট থেকে ফর্ম জমা পর্যালোচনা করুন।",
    ],
  },
  {
    key: "public_website",
    color: "cyan",
    title: "পাবলিক ওয়েবসাইট",
    description: "পাবলিক মুখী ওয়েবসাইট এবং এর বৈশিষ্ট্য সম্পর্কে জানুন।",
    steps: [
      "পাবলিক সাইট Laravel Blade এবং Tailwind CSS দিয়ে তৈরি।",
      "সকল কনটেন্ট ড্যাশবোর্ড সিএমএস বিভাগ থেকে পরিচালিত হয়।",
      "বহু-ভাষা সমর্থন: ইংরেজি এবং বাংলা বিল্ট-ইন।",
      "এসইও ইন্ডেক্সিংয়ের জন্য /sitemap.xml-এ সাইটম্যাপ ব্যবহার করুন।",
    ],
  },
];

const EN_STRINGS: HelpStrings = {
  pageDescription: "Use the sections below to learn how to manage your school efficiently.",
  searchPlaceholder: "Search help topics...",
  noResults: "No matching topics found.",
  wasThisHelpful: "Was this helpful?",
  yes: "Yes",
  no: "No",
};

const BN_STRINGS: HelpStrings = {
  pageDescription: "আপনার স্কুল দক্ষতার সাথে পরিচালনা করতে নিচের অংশগুলো ব্যবহার করুন।",
  searchPlaceholder: "সহায়তা বিষয় অনুসন্ধান করুন...",
  noResults: "কোনো মিলের বিষয় পাওয়া যায়নি।",
  wasThisHelpful: "এটি কি সহায়ক ছিল?",
  yes: "হ্যাঁ",
  no: "না",
};

export function helpContent(forLocale: string = locale()): { sections: HelpSection[]; strings: HelpStrings } {
  const isBn = forLocale === "bn";
  return {
    sections: (isBn ? BN_SECTIONS : EN_SECTIONS).map((s) => ({ ...s })),
    strings: isBn ? BN_STRINGS : EN_STRINGS,
  };
}

export function helpIconColor(key: string): { bg: string; text: string; icon: string } {
  const color = COLORS[key as keyof typeof COLORS] ?? COLORS.brand;
  return color;
}