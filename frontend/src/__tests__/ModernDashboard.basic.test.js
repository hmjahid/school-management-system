import { render, screen } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';

// Mock the AuthContext
jest.mock('../contexts/AuthContext', () => ({
  useAuth: () => ({
    user: { name: 'Test User', role: 'student' }
  })
}));

// Mock the Header component
jest.mock('../components/dashboard/Header', () => ({
  __esModule: true,
  default: () => <div data-testid="mock-header">Header</div>
}));

describe('ModernDashboard - Basic Test', () => {
  it('renders without crashing', () => {
    // Import dynamically to avoid hoisting issues with mocks
    const ModernDashboard = require('../pages/dashboard/ModernDashboard').default;
    
    render(
      <BrowserRouter>
        <ModernDashboard />
      </BrowserRouter>
    );
    
    expect(screen.getByTestId('mock-header')).toBeInTheDocument();
  });
});
