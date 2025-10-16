import React from 'react';
import { Formik, Form, Field } from 'formik';

export default function TestFormik() {
  return (
    <div>
      <h1>Test Formik</h1>
      <Formik
        initialValues={{ test: '' }}
        onSubmit={(values) => {
          console.log(values);
        }}
      >
        <Form>
          <Field name="test" />
          <button type="submit">Submit</button>
        </Form>
      </Formik>
    </div>
  );
}
