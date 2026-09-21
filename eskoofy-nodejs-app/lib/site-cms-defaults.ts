/**
 * Canonical home-page default content, mirroring the app's `site_ui('home.*')`
 * nexted arrays in `lang/{en,bn}/site_frontend.php`. Scalar leaf strings live
 * in `lang/{en,bn}.ts`; these array-shaped defaults cannot be flattened into the
 * generated scalar dictionary, so they live here (same values, same shape).
 */

export interface FeatureCard {
  title: string;
  description: string;
}

export interface TestimonialCard {
  quote: string;
  name: string;
  role: string;
}

export interface QuickLink {
  route: string;
  title: string;
  description: string;
}

/** CMS section block — mirrors `page_sections` in lang/{en,bn}/site_frontend.php. */
export interface CmsSection {
  heading?: string;
  paragraphs?: string[];
  bullets?: string[];
  cards?: { title?: string; body?: string }[];
  faq?: { q?: string; a?: string }[];
}

export type PageSectionsDefaults = Record<string, CmsSection[]>;

export const PAGE_SECTIONS_DEFAULTS: Record<string, PageSectionsDefaults> = {
  en: {
    about: [
      { heading: "School history & mission", paragraphs: [
        "Founded to serve the local community with affordable, quality education, we have grown into a trusted institution over the years.",
        "Our mission is to nurture critical thinking, integrity, and service-minded graduates prepared for the world beyond school.",
      ]},
      { heading: "Vision & core values", paragraphs: [
        "We aspire to be a model school where innovation and inclusion drive measurable student success.",
      ], bullets: ["Excellence", "Integrity", "Respect", "Collaboration", "Resilience"] },
      { heading: "Principal's message", paragraphs: [
        "Education is a partnership between school, students, and families. Together we build the foundation for a meaningful life.",
      ]},
      { heading: "School administration", paragraphs: [
        "Led by the principal and heads of sections, supported by admissions, finance, and operations teams.",
      ]},
      { heading: "Infrastructure & facilities", bullets: [
        "Science and computer labs",
        "Library and reading rooms",
        "Sports grounds",
        "Auditorium",
        "Safe transport (where applicable)",
      ]},
      { heading: "Achievements & accreditations", paragraphs: [
        "Board examination results, national competition placements, and community service recognition are published annually.",
      ]},
      { heading: "School anthem & emblem", paragraphs: [
        "Lyrics and emblem usage guidelines are available from the school office and can be shared upon request.",
      ]},
    ],
    academics: [
      { heading: "Curriculum overview", paragraphs: [
        "National curriculum expectations are met and extended through project work, labs, and excursions.",
      ]},
      { heading: "Academic calendar", paragraphs: [
        "Term dates, holidays, and assessment windows are published at the start of the academic year.",
      ]},
      { heading: "Subjects offered", cards: [
        { title: "Sciences", body: "Physics, chemistry, biology, and integrated STEM projects." },
        { title: "Arts & humanities", body: "Literature, visual arts, music, and drama." },
        { title: "Commerce & business", body: "Accounting, business studies, and economics foundations." },
        { title: "Technology", body: "Programming, digital literacy, and responsible technology use." },
      ]},
      { heading: "Co-curricular activities", paragraphs: [
        "Clubs, debates, robotics, and community service complement classroom learning.",
      ]},
      { heading: "Assessment & evaluation", paragraphs: [
        "Continuous assessment plus term examinations; integrity policies apply throughout.",
      ]},
      { heading: "Results & reports", paragraphs: [
        "Published results for students and parents are available in the portal each term.",
      ]},
    ],
    admissions: [
      { heading: "Admission process", bullets: [
        "Submit the online application with documents",
        "Admissions review and entrance test (if applicable)",
        "Interview with the family",
        "Enrollment and class placement",
      ]},
      { heading: "Documents required", bullets: [
        "Birth certificate",
        "Previous school records / transfer certificate",
        "Passport-size photo",
        "Parent/guardian ID (where required)",
      ]},
      { heading: "Fee structure", paragraphs: [
        "See the payments portal for current fee heads. Installment options may be available on request.",
      ]},
      { heading: "Important dates", paragraphs: [
        "Application window, test dates, and orientation are announced each cycle on the news page.",
      ]},
      { heading: "Admission test syllabus", paragraphs: [
        "Age-appropriate literacy, numeracy, and reasoning items align with the prior grade outcomes.",
      ]},
      { heading: "Frequently asked questions", faq: [
        { q: "Can I save a draft online?", a: "Public applications submit in one step; contact admissions if you need an extension." },
        { q: "How do I pay fees?", a: "Use the fee payment portal or approved bank channels listed there." },
      ]},
    ],
    students: [
      { heading: "Student council", paragraphs: ["Elected representatives voice student ideas and lead service initiatives."] },
      { heading: "Clubs & societies", bullets: ["Debate", "Science club", "Sports teams", "Community service", "Arts guild"] },
      { heading: "Academic calendar", paragraphs: ["Key dates mirror the academics page and are shared in homeroom."] },
      { heading: "Exam schedule", paragraphs: ["Detailed schedules are posted in the portal before each term."] },
      { heading: "Results", paragraphs: ["Published results appear in the student/parent portal."] },
      { heading: "School uniform", paragraphs: ["Dress code supports safety and equality; specifics are in the student handbook."] },
      { heading: "Code of conduct", paragraphs: ["Respect, honesty, and digital citizenship expectations apply on campus and online."] },
      { heading: "Student resources", paragraphs: ["Library hours, learning support, and counselling booking via the office."] },
    ],
    faculty: [
      { heading: "Teaching staff directory", paragraphs: ["Profiles below are synced from the school information system; published entries appear on the public site."] },
      { heading: "Administrative staff", paragraphs: ["Admissions, finance, IT, and operations teams support daily school life."] },
      { heading: "Faculty achievements", paragraphs: ["Workshops, certifications, and research contributions are highlighted each year."] },
      { heading: "Professional development", paragraphs: ["Ongoing training in pedagogy, safeguarding, and subject depth."] },
      { heading: "Teacher of the month", paragraphs: ["Recognising innovation and student impact — see news for current honourees."] },
      { heading: "Faculty resources", paragraphs: ["Internal LMS and shared drives are available to staff accounts."] },
    ],
    news: [
      { heading: "School magazine & newsletter", paragraphs: ["PDF archives can be linked from structured CMS sections when files are uploaded."] },
      { heading: "Press releases", paragraphs: ["Official statements are published here and may be distributed to local media."] },
    ],
    gallery: [
      { heading: "Photo gallery categories", bullets: ["Academic activities", "Cultural events", "Sports", "Annual functions", "Field trips"] },
    ],
    contact: [
      { heading: "School hours", paragraphs: ["Office hours typically follow the timetable in website settings; adjust in admin settings for accuracy."] },
    ],
    committee: [
      { heading: "About the committee", paragraphs: [
        "The managing committee provides strategic oversight and governance to ensure the school achieves its educational mission and maintains the highest standards.",
      ]},
      { heading: "Roles and responsibilities", paragraphs: [
        "Committee members are responsible for policy decisions, financial oversight, strategic planning, and ensuring accountability in all school operations.",
      ]},
    ],
    terms: [
      { heading: "Use of the website", paragraphs: ["This site is provided for information about the school, admissions, and announcements."] },
      { heading: "Accounts and portal", paragraphs: ["Portal accounts are for authorised parents, students, and staff. You are responsible for keeping credentials secure."] },
      { heading: "Intellectual property", paragraphs: ["Text, images, logos, and materials on this site are owned by the school unless stated otherwise."] },
      { heading: "Limitation of liability", paragraphs: ["Information on this site is provided in good faith. The school is not liable for decisions based solely on this content."] },
      { heading: "Contact", paragraphs: ["Questions about these terms can be directed to the school office."] },
    ],
  },
  bn: {
    about: [
      { heading: "বিদ্যালয়ের ইতিহাস ও লক্ষ্য", paragraphs: [
        "সাশ্রয়ী ও মানসম্মত শিক্ষার মাধ্যমে স্থানীয় সমাজের সেবায় প্রতিষ্ঠিত, বছরের পর বছর আমরা একটি বিশ্বস্ত প্রতিষ্ঠানে পরিণত হয়েছি।",
        "আমাদের লক্ষ্য — সমালোচনামূলক চিন্তা, সততা ও সেবামুখী মনোভাবসম্পন্ন এমন গ্র্যাজুয়েট তৈরি করা যারা বিদ্যালয়ের বাইরের জগতের জন্য প্রস্তুত।",
      ]},
      { heading: "দৃষ্টিভঙ্গি ও মূল মূল্যবোধ", paragraphs: [
        "আমরা এমন একটি আদর্শ বিদ্যালয় হতে চাই যেখানে উদ্ভাবন ও অন্তর্ভুক্তি শিক্ষার্থীদের সাফল্যকে এগিয়ে নিয়ে যায়।",
      ], bullets: ["শ্রেষ্ঠত্ব", "সততা", "শ্রদ্ধা", "সহযোগিতা", "সহনশীলতা"] },
      { heading: "প্রধান শিক্ষকের বার্তা", paragraphs: [
        "শিক্ষা হলো বিদ্যালয়, শিক্ষার্থী ও পরিবারের মধ্যে একটি অংশীদারিত্ব। একসাথে আমরা অর্থবহ জীবনের ভিত্তি তৈরি করি।",
      ]},
      { heading: "বিদ্যালয়ের প্রশাসন", paragraphs: [
        "প্রধান শিক্ষক ও বিভাগীয় প্রধানদের নেতৃত্বে ভর্তি, অর্থ ও পরিচালন বিভাগ সহায়তা প্রদান করে।",
      ]},
      { heading: "অবকাঠামো ও সুবিধাসমূহ", bullets: [
        "বিজ্ঞান ও কম্পিউটার ল্যাব",
        "গ্রন্থাগার ও পড়ার কক্ষ",
        "খেলার মাঠ",
        "অডিটোরিয়াম",
        "নিরাপদ পরিবহন (যথাযথ ক্ষেত্রে)",
      ]},
      { heading: "অর্জন ও স্বীকৃতি", paragraphs: [
        "বোর্ড পরীক্ষার ফলাফল, জাতীয় প্রতিযোগিতায় সাফল্য ও সমাজসেবার স্বীকৃতি প্রতিবছর প্রকাশিত হয়।",
      ]},
      { heading: "বিদ্যালয়ের সংগীত ও প্রতীক", paragraphs: [
        "সংগীতের কথা ও প্রতীক ব্যবহারের নির্দেশনা বিদ্যালয়ের কার্যালয় থেকে অনুরোধে পাওয়া যায়।",
      ]},
    ],
    academics: [
      { heading: "পাঠ্যক্রমের সারসংক্ষেপ", paragraphs: [
        "জাতীয় পাঠ্যক্রমের প্রত্যাশা পূরণ করা হয় এবং প্রকল্পকাজ, ল্যাব ও শিক্ষা সফরের মাধ্যমে তা সম্প্রসারিত হয়।",
      ]},
      { heading: "একাডেমিক ক্যালেন্ডার", paragraphs: [
        "টার্মের তারিখ, ছুটি ও মূল্যায়নের সময়সীমা শিক্ষাবর্ষের শুরুতে প্রকাশিত হয়।",
      ]},
      { heading: "প্রদত্ত বিষয়সমূহ", cards: [
        { title: "বিজ্ঞান", body: "পদার্থবিজ্ঞান, রসায়ন, জীববিজ্ঞান ও সমন্বিত এসটিইএম প্রকল্প।" },
        { title: "কলা ও মানবিক", body: "সাহিত্য, চারুকলা, সংগীত ও নাটক।" },
        { title: "বাণিজ্য", body: "হিসাববিজ্ঞান, ব্যবসায় শিক্ষা ও অর্থনীতির ভিত্তি।" },
        { title: "প্রযুক্তি", body: "প্রোগ্রামিং, ডিজিটাল সাক্ষরতা ও দায়িত্বশীল প্রযুক্তি ব্যবহার।" },
      ]},
      { heading: "সহপাঠ্যক্রম কার্যক্রম", paragraphs: [
        "ক্লাব, বিতর্ক, রোবোটিকস ও সমাজসেবা শ্রেণিকক্ষের শেখাকে পরিপূরক করে।",
      ]},
      { heading: "মূল্যায়ন", paragraphs: [
        "ধারাবাহিক মূল্যায়নের পাশাপাশি টার্ম পরীক্ষা অনুষ্ঠিত হয়; সততা নীতি সর্বত্র প্রযোজ্য।",
      ]},
      { heading: "ফলাফল ও প্রতিবেদন", paragraphs: [
        "প্রতিটি টার্মে শিক্ষার্থী ও অভিভাবকদের জন্য ফলাফল পোর্টালে প্রকাশিত হয়।",
      ]},
    ],
    admissions: [
      { heading: "ভর্তি প্রক্রিয়া", bullets: [
        "নথিসহ অনলাইনে আবেদন জমা দিন",
        "ভর্তি পর্যালোচনা ও প্রবেশ পরীক্ষা (প্রযোজ্য হলে)",
        "পরিবারের সাক্ষাৎকার",
        "ভর্তি ও শ্রেণি বরাদ্দ",
      ]},
      { heading: "প্রয়োজনীয় নথি", bullets: [
        "জন্ম নিবন্ধন",
        "পূর্ববর্তী বিদ্যালয়ের রেকর্ড / স্থানান্তর সনদ",
        "পাসপোর্ট সাইজের ছবি",
        "অভিভাবকের পরিচয়পত্র (প্রয়োজনে)",
      ]},
      { heading: "ফি কাঠামো", paragraphs: [
        "বর্তমান ফি হেডগুলোর জন্য পেমেন্ট পোর্টাল দেখুন। অনুরোধে কিস্তি সুবিধা পাওয়া যেতে পারে।",
      ]},
      { heading: "গুরুত্বপূর্ণ তারিখ", paragraphs: [
        "আবেদনের সময়সীমা, পরীক্ষার তারিখ ও ওরিয়েন্টেশন প্রতিটি চক্রে সংবাদ পাতায় ঘোষণা করা হয়।",
      ]},
      { heading: "প্রবেশ পরীক্ষার সিলেবাস", paragraphs: [
        "বয়স-উপযোগী সাক্ষরতা, গণনা ও যুক্তির প্রশ্ন পূর্ববর্তী শ্রেণির ফলাফলের সাথে সামঞ্জস্যপূর্ণ।",
      ]},
      { heading: "সচরাচর জিজ্ঞাসা", faq: [
        { q: "আমি কি অনলাইনে খসড়া সংরক্ষণ করতে পারি?", a: "পাবলিক আবেদন এক ধাপে জমা হয়; সময় বাড়াতে ভর্তি শাখায় যোগাযোগ করুন।" },
        { q: "ফি কীভাবে প্রদান করব?", a: "ফি পেমেন্ট পোর্টাল বা সেখানে তালিকাভুক্ত অনুমোদিত ব্যাংক চ্যানেল ব্যবহার করুন।" },
      ]},
    ],
    students: [
      { heading: "ছাত্র কাউন্সিল", paragraphs: ["নির্বাচিত প্রতিনিধিরা শিক্ষার্থীদের ধারণা তুলে ধরেন ও সেবামূলক উদ্যোগ পরিচালনা করেন।"] },
      { heading: "ক্লাব ও সংগঠন", bullets: ["বিতর্ক", "বিজ্ঞান ক্লাব", "ক্রীড়া দল", "সমাজসেবা", "শিল্পকলা সংঘ"] },
      { heading: "একাডেমিক ক্যালেন্ডার", paragraphs: ["গুরুত্বপূর্ণ তারিখগুলো একাডেমিক পাতার সাথে সামঞ্জস্যপূর্ণ এবং হোমরুমে শেয়ার করা হয়।"] },
      { heading: "পরীক্ষার সময়সূচি", paragraphs: ["প্রতিটি টার্মের আগে বিস্তারিত সময়সূচি পোর্টালে প্রকাশিত হয়।"] },
      { heading: "ফলাফল", paragraphs: ["প্রকাশিত ফলাফল শিক্ষার্থী/অভিভাবক পোর্টালে দেখা যায়।"] },
      { heading: "বিদ্যালয়ের পোশাক", paragraphs: ["পোশাকবিধি নিরাপত্তা ও সমতাকে সমর্থন করে; বিস্তারিত শিক্ষার্থী নির্দেশিকায় রয়েছে।"] },
      { heading: "আচরণবিধি", paragraphs: ["ক্যাম্পাসে ও অনলাইনে শ্রদ্ধা, সততা ও ডিজিটাল নাগরিকতার প্রত্যাশা প্রযোজ্য।"] },
      { heading: "শিক্ষার্থী সম্পদ", paragraphs: ["গ্রন্থাগারের সময়সূচি, শেখার সহায়তা ও কাউন্সেলিং কার্যালয়ের মাধ্যমে উপলব্ধ।"] },
    ],
    faculty: [
      { heading: "শিক্ষকমণ্ডলীর তালিকা", paragraphs: ["নিচের প্রোফাইলগুলো বিদ্যালয়ের তথ্য ব্যবস্থা থেকে সিঙ্ক হয়; প্রকাশিত এন্ট্রিগুলো সর্বজনীন সাইটে দেখা যায়।"] },
      { heading: "প্রশাসনিক কর্মী", paragraphs: ["ভর্তি, অর্থ, আইটি ও পরিচালন বিভাগ দৈনন্দিন বিদ্যালয় জীবনে সহায়তা করে।"] },
      { heading: "শিক্ষকদের অর্জন", paragraphs: ["ওয়ার্কশপ, সার্টিফিকেশন ও গবেষণা অবদান প্রতিবছর তুলে ধরা হয়।"] },
      { heading: "পেশাগত উন্নয়ন", paragraphs: ["শিক্ষাবিজ্ঞান, সুরক্ষা ও বিষয় গভীরতায় চলমান প্রশিক্ষণ।"] },
      { heading: "মাসের শ্রেষ্ঠ শিক্ষক", paragraphs: ["উদ্ভাবন ও শিক্ষার্থীদের প্রভাবকে স্বীকৃতি দিয়ে বর্তমান সম্মানিতদের জন্য সংবাদ দেখুন।"] },
      { heading: "শিক্ষক সম্পদ", paragraphs: ["অভ্যন্তরীণ এলএমএস ও শেয়ার্ড ড্রাইভ কর্মী অ্যাকাউন্টের জন্য উপলব্ধ।"] },
    ],
    news: [
      { heading: "বিদ্যালয়ের পত্রিকা ও নিউজলেটার", paragraphs: ["ফাইল আপলোড হলে পিডিএফ আর্কাইভ স্ট্রাকচার্ড সিএমএস সেকশন থেকে লিঙ্ক করা যায়।"] },
      { heading: "প্রেস রিলিজ", paragraphs: ["সরকারি বিবৃতি এখানে প্রকাশিত হয় এবং স্থানীয় গণমাধ্যমে বিতরণ করা হতে পারে।"] },
    ],
    gallery: [
      { heading: "ফটো গ্যালারি ক্যাটাগরি", bullets: ["একাডেমিক কার্যক্রম", "সাংস্কৃতিক অনুষ্ঠান", "ক্রীড়া", "বার্ষিক অনুষ্ঠান", "শিক্ষা সফর"] },
    ],
    contact: [
      { heading: "বিদ্যালয়ের কার্যালয়ের সময়", paragraphs: ["কার্যালয়ের সময় সাধারণত ওয়েবসাইট সেটিংসে দেওয়া সময়সূচি অনুসরণ করে; নির্ভুলতার জন্য অ্যাডমিন সেটিংসে সামঞ্জস্য করুন।"] },
    ],
    terms: [
      { heading: "ওয়েবসাইটের ব্যবহার", paragraphs: ["এই সাইটটি বিদ্যালয়, ভর্তি ও ঘোষণা সম্পর্কে তথ্য প্রদানের জন্য তৈরি।"] },
      { heading: "অ্যাকাউন্ট ও পোর্টাল", paragraphs: ["পোর্টাল অ্যাকাউন্ট শুধুমাত্র অনুমোদিত অভিভাবক, শিক্ষার্থী ও কর্মীদের জন্য। ক্রেডেনশিয়াল সুরক্ষিত রাখা আপনার দায়িত্ব।"] },
      { heading: "বৌদ্ধিক সম্পত্তি", paragraphs: ["এই সাইটের পাঠ্য, চিত্র, লোগো ও উপকরণগুলো বিদ্যালয়ের মালিকানাধীন, যদি না অন্যথায় উল্লেখ থাকে।"] },
      { heading: "দায়ের সীমাবদ্ধতা", paragraphs: ["এই সাইটের তথ্য সদিচ্ছায় প্রদান করা হয়। শুধুমাত্র এই বিষয়বস্তুর উপর ভিত্তি করে নেওয়া সিদ্ধান্তের জন্য বিদ্যালয় দায়ী নয়।"] },
      { heading: "যোগাযোগ", paragraphs: ["এই শর্তাবলী সম্পর্কে প্রশ্ন বিদ্যালয়ের কার্যালয়ে পাঠানো যেতে পারে।"] },
    ],
  },
};

export interface HomeDefaults {
  features: FeatureCard[];
  highlights: string[];
  testimonials: TestimonialCard[];
  quickLinks: QuickLink[];
}

export const HOME_DEFAULTS: Record<string, HomeDefaults> = {
  en: {
    features: [
      { title: "Experienced faculty", description: "Dedicated educators with deep subject expertise and care for every learner." },
      { title: "Modern facilities", description: "Classrooms and labs that support hands-on, collaborative learning." },
      { title: "Balanced curriculum", description: "Academic rigour alongside arts, sports, and character development." },
      { title: "Inclusive community", description: "A welcoming environment where diversity is celebrated." },
    ],
    highlights: [
      "Holistic curriculum with STEM and the arts",
      "Competitive sports and cultural events",
      "Counselling and university guidance",
      "Inclusive, safe, and supportive campus",
    ],
    testimonials: [
      { quote: "The teachers genuinely care — my daughter has flourished here.", name: "Parent", role: "Class V family" },
      { quote: "Strong academics and clubs — I found my passion for science.", name: "Student", role: "Class XI" },
    ],
    quickLinks: [
      { route: "admissions.apply", title: "Admissions", description: "Online application and documents" },
      { route: "portal", title: "Parent / student portal", description: "Attendance, fees, and assignments" },
      { route: "admissions.status", title: "Application status", description: "Track with your application number" },
    ],
  },
  bn: {
    features: [
      { title: "অভিজ্ঞ শিক্ষকমণ্ডলী", description: "বিষয়ে দক্ষ ও প্রতিটি শিক্ষার্থীর প্রতি যত্নশীল শিক্ষক।" },
      { title: "আধুনিক সুবিধা", description: "হাতে-কলমে ও দলগত শেখার জন্য উপযোগী ক্লাসরুম ও ল্যাব।" },
      { title: "সুষম পাঠ্যক্রম", description: "একাডেমিক কঠোরতার পাশাপাশি কলা, ক্রীড়া ও চরিত্র গঠন।" },
      { title: "অন্তর্ভুক্তিমূলক সম্প্রদায়", description: "বৈচিত্র্যকে স্বাগত জানায় এমন পরিবেশ।" },
    ],
    highlights: [
      "বিজ্ঞান ও শিল্পকলাসহ সামগ্রিক পাঠ্যক্রম",
      "প্রতিযোগিতামূলক ক্রীড়া ও সাংস্কৃতিক অনুষ্ঠান",
      "কাউন্সেলিং ও বিশ্ববিদ্যালয় নির্দেশনা",
      "অন্তর্ভুক্তিমূলক, নিরাপদ ও সহায়ক ক্যাম্পাস",
    ],
    testimonials: [
      { quote: "শিক্ষকরা সত্যিই যত্নশীল — আমার মেয়ে এখানে অনেক এগিয়েছে।", name: "অভিভাবক", role: "পঞ্চম শ্রেণি" },
      { quote: "শক্তিশালী একাডেমিক কার্যক্রম ও ক্লাব — বিজ্ঞানের প্রতি আমার আগ্রহ এখান থেকেই।", name: "শিক্ষার্থী", role: "একাদশ শ্রেণি" },
    ],
    quickLinks: [
      { route: "admissions.apply", title: "ভর্তি", description: "অনলাইন আবেদন ও নথিপত্র" },
      { route: "portal", title: "অভিভাবক / শিক্ষার্থী পোর্টাল", description: "উপস্থিতি, ফি ও অ্যাসাইনমেন্ট" },
      { route: "admissions.status", title: "আবেদনের অবস্থা", description: "আবেদন নম্বর দিয়ে ট্র্যাক করুন" },
    ],
  },
};