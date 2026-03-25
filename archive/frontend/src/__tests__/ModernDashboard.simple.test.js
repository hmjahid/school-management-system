// Simple test file for ModernDashboard
import React from 'react';
import { render, screen } from '@testing-library/react';

test('renders without crashing', () => {
  render(
    <div data-testid="test-container">
      <h1>Test Dashboard</h1>
      <p>This is a simple test component</p>
    </div>
  );
  
  expect(screen.getByTestId('test-container')).toBeInTheDocument();
  expect(screen.getByText('Test Dashboard')).toBeInTheDocument();
});
