import React from 'react';
import { Card, CardBody } from 'reactstrap';
import { FaArrowUp, FaArrowDown, FaMinus } from 'react-icons/fa';

const StatCard = ({
  title,
  value,
  icon,
  trend = 0,
  trendLabel = '',
  trendSuffix = '',
  color = 'primary',
  className = '',
}) => {
  const trendValue = parseFloat(trend) || 0;
  const isPositive = trendValue > 0;
  const isNeutral = trendValue === 0;

  return (
    <Card className={`mb-4 border-0 shadow-sm ${className}`}>
      <CardBody className="p-3">
        <div className="d-flex justify-content-between align-items-center">
          <div>
            <h6 className="text-uppercase text-muted mb-1 small">{title}</h6>
            <h2 className="mb-0">{value}</h2>
          </div>
          <div
            className={`icon-shape icon-lg bg-${color}-light text-${color} rounded-circle`}
          >
            {icon}
          </div>
        </div>
        
        {trendLabel && (
          <div className="mt-3 d-flex align-items-center">
            {!isNeutral ? (
              <span className={`text-${isPositive ? 'success' : 'danger'} d-flex align-items-center`}>
                {isPositive ? <FaArrowUp className="me-1" /> : <FaArrowDown className="me-1" />}
                {Math.abs(trendValue)}
                {trendSuffix}
              </span>
            ) : (
              <span className="text-muted d-flex align-items-center">
                <FaMinus className="me-1" />
                {trendSuffix ? `0${trendSuffix}` : 'No change'}
              </span>
            )}
            <span className="text-muted ms-2">{trendLabel}</span>
          </div>
        )}
      </CardBody>
    </Card>
  );
};

export default StatCard;
