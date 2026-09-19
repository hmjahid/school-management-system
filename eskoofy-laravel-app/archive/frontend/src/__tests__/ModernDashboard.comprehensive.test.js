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

describe('ModernDashboard Component', () => {
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

    // Verify student-specific content
    expect(screen.getByText(/welcome back, test user!/i)).toBeInTheDocument();
    expect(screen.getByText(/upcoming classes/i)).toBeInTheDocument();
    expect(screen.getByText(/pending assignments/i)).toBeInTheDocument();
    expect(screen.getByText(/average grade/i)).toBeInTheDocument();
  });

  it('displays teacher dashboard for teacher role', async () => {
    useAuth.mockReturnValue({ 
      user: { ...mockUser, role: 'teacher' } 
    });

    render(
      <BrowserRouter>
        <ModernDashboard />
      </BrowserRouter>
    );

    await waitFor(() => {
      expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    // Verify teacher-specific content
    expect(screen.getByText(/teacher dashboard/i)).toBeInTheDocument();
  });

  it('displays admin dashboard for admin role', async () => {
    useAuth.mockReturnValue({ 
      user: { ...mockUser, role: 'admin' } 
    });

    render(
      <BrowserRouter>
        <ModernDashboard />
      </BrowserRouter>
    );

    await waitFor(() => {
      expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    // Verify admin-specific content
    expect(screen.getByText(/admin dashboard/i)).toBeInTheDocument();
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

    // Verify stats cards
    expect(screen.getByText('3')).toBeInTheDocument(); // Upcoming Classes
    expect(screen.getByText('2')).toBeInTheDocument(); // Pending Assignments
    expect(screen.getByText('A-')).toBeInTheDocument(); // Average Grade
    expect(screen.getByText('94.5%')).toBeInTheDocument(); // Attendance Rate
  });

  it('displays recent announcements', async () => {
    render(
      <BrowserRouter>
        <ModernDashboard />
      </BrowserRouter>
    );

    await waitFor(() => {
      expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    // Verify announcements
    expect(screen.getByText(/recent announcements/i)).toBeInTheDocument();
    expect(screen.getByText(/school picnic/i)).toBeInTheDocument();
    expect(screen.getByText(/science fair/i)).toBeInTheDocument();
  });

  it('displays upcoming exams', async () => {
    render(
      <BrowserRouter>
        <ModernDashboard />
      </BrowserRouter>
    );

    await waitFor(() => {
      expect(screen.queryByRole('status')).not.toBeInTheDocument();
    });

    // Verify upcoming exams
    expect(screen.getByText(/upcoming exams/i)).toBeInTheDocument();
    expect(screen.getByText(/mathematics/i)).toBeInTheDocument();
    expect(screen.getByText(/science/i)).toBeInTheDocument();
  });
});
