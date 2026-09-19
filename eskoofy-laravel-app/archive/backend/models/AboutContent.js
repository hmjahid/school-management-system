const mongoose = require('mongoose');

const aboutContentSchema = new mongoose.Schema({
  hero: {
    title: { type: String, required: true },
    subtitle: { type: String, required: true },
    image: { type: String, required: true }
  },
  mission: { type: String, required: true },
  vision: { type: String, required: true },
  history: {
    text: { type: String, required: true },
    image: { type: String, required: true }
  },
  values: [{
    title: { type: String, required: true },
    description: { type: String, required: true }
  }],
  stats: [{
    number: { type: String, required: true },
    label: { type: String, required: true }
  }],
  leadership: [{
    name: { type: String, required: true },
    role: { type: String, required: true },
    bio: { type: String, required: true },
    image: { type: String, required: true }
  }],
  contact: {
    address: { type: String, required: true },
    phone: { type: String, required: true },
    email: { type: String, required: true },
    admissionEmail: { type: String, required: true },
    hours: { type: String, required: true }
  },
  lastUpdated: { type: Date, default: Date.now }
});

// Create a single document for about content
aboutContentSchema.statics.getContent = async function() {
  let content = await this.findOne();
  if (!content) {
    // Create default content if none exists
    content = await this.create({
      hero: {
        title: 'About Our School',
        subtitle: 'Nurturing minds, building character, and shaping futures since 1995',
        image: '/images/hero-bg.jpg'
      },
      mission: 'To empower students to become compassionate, confident, and capable individuals who are prepared to meet the challenges of a rapidly changing world with integrity and purpose.',
      vision: 'To be a beacon of educational excellence, innovation, and character development, where every student discovers their unique potential and is inspired to make a positive impact in their community and beyond.',
      history: {
        text: 'Founded in 1995, our school began as a small educational institution with just 50 students and a handful of dedicated teachers. Over the years, we have grown into a thriving learning community that serves over 1,000 students from diverse backgrounds.',
        image: '/images/school-history.jpg'
      },
      values: [
        {
          title: 'Excellence',
          description: 'We strive for the highest standards in academics, character development, and personal growth.'
        },
        {
          title: 'Community',
          description: 'We foster a supportive and inclusive environment that values diversity and collaboration.'
        },
        {
          title: 'Lifelong Learning',
          description: 'We cultivate curiosity and a love for learning that extends beyond the classroom.'
        }
      ],
      stats: [
        { number: '25+', label: 'Years of Excellence' },
        { number: '1000+', label: 'Students Enrolled' },
        { number: '80+', label: 'Qualified Staff' },
        { number: '95%', label: 'Graduation Rate' }
      ],
      leadership: [
        {
          name: 'Dr. Sarah Johnson',
          role: 'Principal',
          bio: 'With over 20 years of experience in education, Dr. Johnson leads our school with vision and dedication to academic excellence.',
          image: '/images/principal.jpg'
        },
        {
          name: 'Michael Chen',
          role: 'Vice Principal',
          bio: 'A passionate educator with expertise in curriculum development and student engagement strategies.',
          image: '/images/vice-principal.jpg'
        },
        {
          name: 'Priya Patel',
          role: 'Head of Academics',
          bio: 'Committed to fostering innovative teaching methods and supporting our teaching staff in delivering exceptional education.',
          image: '/images/head-academics.jpg'
        }
      ],
      contact: {
        address: '123 Education Street\nCity, State 12345',
        phone: '+1 (555) 123-4567',
        email: 'info@schoolname.edu',
        admissionEmail: 'admissions@schoolname.edu',
        hours: 'Mon-Fri, 8:00 AM - 4:00 PM'
      }
    });
  }
  return content;
};

module.exports = mongoose.model('AboutContent', aboutContentSchema);
