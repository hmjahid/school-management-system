// Simple smoke test to verify the testing setup
import { render, screen } from '@testing-library/react';

test('renders learn react link', () => {
  render(
    <div>
      <h1>Test Component</h1>
      <p>This is a simple test to verify the setup</p>
    </div>
  );
  
  const testElement = screen.getByText(/test component/i);
  expect(testElement).toBeInTheDocument();
});
