import React from 'react';
import PropTypes from 'prop-types';

/**
 * A reusable loading spinner component
 * @param {Object} props - Component props
 * @param {boolean} [props.fullScreen=false] - Whether the spinner should take up the full viewport
 * @param {string} [props.size='h-8 w-8'] - Size of the spinner
 * @param {string} [props.color='indigo'] - Color of the spinner
 * @returns {JSX.Element} Loading spinner component
 */
const LoadingSpinner = ({ fullScreen = false, size = 'h-8 w-8', color = 'indigo' }) => {
  // Using a fixed color class to ensure Tailwind includes it in the build
  const spinner = (
    <div className={`animate-spin rounded-full ${size} border-b-2 border-indigo-500`}></div>
  );

  if (fullScreen) {
    return (
      <div className="fixed inset-0 flex items-center justify-center bg-white bg-opacity-75 z-50">
        {spinner}
      </div>
    );
  }

  return <div className="flex justify-center items-center">{spinner}</div>;
};

LoadingSpinner.propTypes = {
  fullScreen: PropTypes.bool,
  size: PropTypes.string,
  color: PropTypes.string,
};

export default LoadingSpinner;
