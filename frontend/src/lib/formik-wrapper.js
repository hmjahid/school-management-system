// Wrapper for Formik to work with Vite
import * as FormikModule from 'formik/dist/formik.esm.js';

// Re-export all named exports
export const {
  FormikProvider,
  Form,
  Field,
  ErrorMessage,
  useFormik,
  // Add any other Formik exports you need
} = FormikModule;

// Default export for backward compatibility
const Formik = {
  Provider: FormikProvider,
  Form,
  Field,
  ErrorMessage,
  useFormik,
};

export default Formik;
