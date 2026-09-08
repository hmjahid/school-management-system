import React from 'react';
import { Badge } from 'reactstrap';
import { 
  FaUserPlus, 
  FaMoneyBillWave, 
  FaClipboardCheck, 
  FaBook, 
  FaBullhorn,
  FaEllipsisH 
} from 'react-icons/fa';

const getActivityIcon = (type) => {
  switch (type) {
    case 'enrollment':
      return <FaUserPlus className="text-success" />;
    case 'payment':
      return <FaMoneyBillWave className="text-primary" />;
    case 'attendance':
      return <FaClipboardCheck className="text-info" />;
    case 'assignment':
      return <FaBook className="text-warning" />;
    case 'announcement':
      return <FaBullhorn className="text-danger" />;
    default:
      return <FaEllipsisH className="text-muted" />;
  }
};

const getActivityColor = (type) => {
  switch (type) {
    case 'enrollment':
      return 'success';
    case 'payment':
      return 'primary';
    case 'attendance':
      return 'info';
    case 'assignment':
      return 'warning';
    case 'announcement':
      return 'danger';
    default:
      return 'secondary';
  }
};

const RecentActivity = ({ activities = [] }) => {
  const defaultActivities = [
    {
      id: 1,
      type: 'enrollment',
      title: 'New Student Enrollment',
      message: 'John Doe has been enrolled in Class 10-A',
      time: '2 hours ago',
    },
    {
      id: 2,
      type: 'payment',
      title: 'Fee Payment Received',
      message: 'Monthly fee received from Sarah Johnson',
      time: '5 hours ago',
    },
    {
      id: 3,
      type: 'attendance',
      title: 'Attendance Marked',
      message: 'Attendance marked for Class 9-B (95% present)',
      time: '1 day ago',
    },
    {
      id: 4,
      type: 'assignment',
      title: 'New Assignment',
      message: 'Math homework assigned to Class 8-A',
      time: '2 days ago',
    },
  ];

  const activityItems = activities.length > 0 ? activities : defaultActivities;

  return (
    <div className="list-group list-group-flush">
      {activityItems.map((activity) => (
        <div 
          key={activity.id} 
          className="list-group-item border-0 px-0 py-3"
        >
          <div className="d-flex align-items-start">
            <div className="me-3">
              <div className={`icon-shape icon-sm bg-${getActivityColor(activity.type)}-light text-${getActivityColor(activity.type)} rounded-circle d-flex align-items-center justify-content-center`} style={{ width: '36px', height: '36px' }}>
                {getActivityIcon(activity.type)}
              </div>
            </div>
            <div className="flex-grow-1">
              <div className="d-flex justify-content-between align-items-center">
                <h6 className="mb-0">{activity.title}</h6>
                <small className="text-muted">{activity.time}</small>
              </div>
              <p className="mb-0 small text-muted">{activity.message}</p>
            </div>
          </div>
        </div>
      ))}
      
      {activityItems.length === 0 && (
        <div className="text-center py-4">
          <p className="text-muted mb-0">No recent activities</p>
        </div>
      )}
    </div>
  );
};

export default RecentActivity;
