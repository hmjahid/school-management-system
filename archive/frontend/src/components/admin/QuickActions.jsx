import React from 'react';
import { Card, CardBody, CardHeader, CardTitle, Row, Col } from 'reactstrap';
import { Link } from 'react-router-dom';
import { FaPlus } from 'react-icons/fa';

const QuickActions = ({ actions = [] }) => {
  const defaultActions = [
    {
      id: 1,
      title: 'Add Student',
      description: 'Register a new student',
      icon: 'user-graduate',
      url: '/admin/students/add',
      color: 'primary',
    },
    {
      id: 2,
      title: 'Create Class',
      description: 'Add a new class',
      icon: 'chalkboard-teacher',
      url: '/admin/classes/create',
      color: 'success',
    },
    {
      id: 3,
      title: 'Record Payment',
      description: 'Record a new payment',
      icon: 'money-bill-wave',
      url: '/admin/payments/record',
      color: 'info',
    },
    {
      id: 4,
      title: 'Send Notice',
      description: 'Send a notice to parents',
      icon: 'envelope',
      url: '/admin/notices/send',
      color: 'warning',
    },
  ];

  const actionItems = actions.length > 0 ? actions : defaultActions;

  return (
    <Card className="mb-4 shadow-sm">
      <CardHeader className="bg-white border-bottom-0">
        <CardTitle tag="h5" className="mb-0">
          Quick Actions
        </CardTitle>
      </CardHeader>
      <CardBody className="p-3">
        <Row className="g-3">
          {actionItems.map((action) => (
            <Col key={action.id} xs={6}>
              <Link
                to={action.url}
                className="text-decoration-none"
              >
                <div className="card h-100 border-0 shadow-sm hover-lift">
                  <div className="card-body text-center p-3">
                    <div 
                      className={`icon-shape icon-lg bg-${action.color}-light text-${action.color} rounded-circle d-inline-flex align-items-center justify-content-center mb-2`}
                      style={{ width: '48px', height: '48px' }}
                    >
                      <i className={`fas fa-${action.icon} fa-lg`}></i>
                    </div>
                    <h6 className="mb-1">{action.title}</h6>
                    <p className="small text-muted mb-0">{action.description}</p>
                  </div>
                </div>
              </Link>
            </Col>
          ))}
        </Row>
        
        <div className="mt-3 text-center">
          <Link to="/admin/quick-actions" className="btn btn-sm btn-outline-primary">
            <FaPlus className="me-1" /> View All Actions
          </Link>
        </div>
      </CardBody>
    </Card>
  );
};

export default QuickActions;
