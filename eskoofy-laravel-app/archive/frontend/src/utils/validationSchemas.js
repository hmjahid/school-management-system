import * as Yup from 'yup';

export const pageSchema = Yup.object().shape({
  title: Yup.string().required('Title is required'),
  slug: Yup.string()
    .required('URL slug is required')
    .matches(
      /^[a-z0-9-]+$/,
      'Only lowercase letters, numbers, and hyphens are allowed'
    ),
  content: Yup.string().required('Content is required'),
  status: Yup.string().oneOf(['draft', 'published'], 'Invalid status'),
  excerpt: Yup.string(),
  featuredImage: Yup.string().url('Must be a valid URL'),
  seoTitle: Yup.string().max(60, 'SEO title is too long'),
  seoDescription: Yup.string().max(160, 'SEO description is too long'),
  seoKeywords: Yup.string(),
});

export const menuItemSchema = Yup.object().shape({
  title: Yup.string().required('Menu item text is required'),
  url: Yup.string().required('URL is required'),
  target: Yup.string().oneOf(['_self', '_blank'], 'Invalid target'),
  type: Yup.string().oneOf(['custom', 'page', 'category'], 'Invalid type'),
  parentId: Yup.string().nullable(),
  order: Yup.number().default(0),
});

export const mediaUploadSchema = Yup.object().shape({
  file: Yup.mixed().required('A file is required'),
  alt: Yup.string(),
  title: Yup.string(),
  description: Yup.string(),
});

export const headerFooterSchema = Yup.object().shape({
  logo: Yup.object().shape({
    url: Yup.string(),
    width: Yup.number().positive().default(150),
    height: Yup.number().positive().default(50),
    alt: Yup.string(),
  }),
  topBar: Yup.object().shape({
    enabled: Yup.boolean().default(true),
    text: Yup.string(),
    contactInfo: Yup.array().of(
      Yup.object().shape({
        type: Yup.string().oneOf(['phone', 'email', 'address', 'hours']),
        label: Yup.string().required('Label is required'),
        value: Yup.string().required('Value is required'),
      })
    ),
  }),
  footer: Yup.object().shape({
    layout: Yup.string().oneOf(['1-column', '2-columns', '3-columns', '4-columns', 'sidebar-content']),
    backgroundColor: Yup.string(),
    textColor: Yup.string(),
    linkColor: Yup.string(),
    linkHoverColor: Yup.string(),
    copyright: Yup.string(),
  }),
});
