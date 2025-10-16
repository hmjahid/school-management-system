import React from 'react';
import { Formik, Form, Field } from 'formik';

const TestFormik = () => {
  return (
    <div>
      <h1>Test Formik</h1>
      <Formik
        initialValues={{ name: '' }}
        onSubmit={(values) => console.log(values)}
      >
        <Form>
          <Field name="name" />
          <button type="submit">Submit</button>
        </Form>
      </Formik>
    </div>
  );
};

export default TestFormik;
