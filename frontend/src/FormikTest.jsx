import React from 'react';
import { Formik, Form, Field } from 'formik';

const FormikTest = () => {
  return (
    <div style={{ padding: '20px' }}>
      <h2>Formik Test Component</h2>
      <Formik
        initialValues={{ test: '' }}
        onSubmit={(values) => {
          alert(JSON.stringify(values, null, 2));
        }}
      >
        {({ handleSubmit }) => (
          <Form onSubmit={handleSubmit}>
            <div>
              <label htmlFor="test">Test Input: </label>
              <Field name="test" type="text" />
            </div>
            <button type="submit" style={{ marginTop: '10px' }}>Submit</button>
          </Form>
        )}
      </Formik>
    </div>
  );
};

export default FormikTest;
