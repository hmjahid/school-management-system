import { render, screen, waitFor } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';
import ModernDashboard from '../pages/dashboard/ModernDashboard';
import { useAuth } from '../contexts/AuthContext';

// Mock the AuthContext
jest.mock('../contexts/AuthContext', () => ({
  useAuth: jest.fn()
}));

// Mock the Header component
jest.mock('../components/dashboard/Header', () => ({
  __esModule: true,
  default: () => <div data-testid="mock-header">Header</div>
}));

// Mock react-chartjs-2 components
jest.mock('react-chartjs-2', () => ({
  Line: () => <div data-testid="line-chart">Line Chart</div>,
  Bar: () => <div data-testid="bar-chart">Bar Chart</div>,
  Doughnut: () => <div data-testid="doughnut-chart">Doughnut Chart</div>,
}));

describe('ModernDashboard', () => {
  // Mock user data
  const mockUser = {
    name: 'Test User',
    role: 'student',
    email: 'test@example.com'
  };

  // Setup mock for each test
  beforeEach(() => {
    useAuth.mockReturnValue({ user: mockUser });
  });

  it('renders loading state initially', () => {
    render(
      <BrowserRouter>
        <ModernDashboard />
      </BrowserRouter>
    );
    
    expect(screen.getByRole('status')).toBeInTheDocument();
  });

  it('displays student dashboard after loading', async () => {
    render(
      <BrowserRouter>
        <ModernDashboard />
      </BrowserRouter>
    );

    // Wait for loading to complete
    await waitFor(() => {
      expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    // Check for student dashboard content
    expect(screen.getByText(/welcome back/i)).toBeInTheDocument();
    expect(screen.getByText(/upcoming classes/i)).toBeInTheDocument();
    expect(screen.getByText(/pending assignments/i)).toBeInTheDocument();
  });

  it('displays stats cards with correct data', async () => {
    render(
      <BrowserRouter>
        <ModernDashboard />
      </BrowserRouter>
    );

    await waitFor(() => {
      expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    // Verify stats cards are rendered
    expect(screen.getByText('3')).toBeInTheDocument(); // Upcoming Classes
    expect(screen.getByText('2')).toBeInTheDocument(); // Pending Assignments
    expect(screen.getByText('A-')).toBeInTheDocument(); // Average Grade
  });
});
