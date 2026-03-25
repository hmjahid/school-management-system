import { useState, useEffect, useRef } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useWidgets } from '../../contexts/WidgetContext';
import { useDashboard } from '../../contexts/DashboardContext';
import { PencilSquareIcon, XMarkIcon, ArrowsPointingOutIcon } from '@heroicons/react/24/outline';

const WidgetContainer = ({ 
  widget, 
  onEdit, 
  onRemove, 
  isEditing = false, 
  children 
}) => {
  const [isHovered, setIsHovered] = useState(false);
  const [isExpanded, setIsExpanded] = useState(false);
  const [height, setHeight] = useState('auto');
  const contentRef = useRef(null);
  const { theme } = useDashboard();

  // Update height when content changes
  useEffect(() => {
    if (contentRef.current) {
      setHeight(contentRef.current.scrollHeight);
    }
  }, [children]);

  // Toggle expanded state
  const toggleExpand = () => {
    setIsExpanded(!isExpanded);
  };

  // Handle edit click
  const handleEdit = (e) => {
    e.stopPropagation();
    if (onEdit) onEdit(widget);
  };

  // Handle remove click
  const handleRemove = (e) => {
    e.stopPropagation();
    if (onRemove) onRemove(widget.id);
  };

  // Animation variants for the widget
  const widgetVariants = {
    hidden: { opacity: 0, y: 20 },
    visible: { 
      opacity: 1, 
      y: 0,
      transition: { 
        type: 'spring', 
        stiffness: 300, 
        damping: 25 
      } 
    },
    exit: { 
      opacity: 0, 
      y: -20,
      transition: { 
        duration: 0.2 
      } 
    }
  };

  // Animation variants for the content
  const contentVariants = {
    collapsed: { 
      height: 0,
      opacity: 0,
      transition: { 
        duration: 0.3,
        ease: 'easeInOut' 
      } 
    },
    expanded: { 
      height: height,
      opacity: 1,
      transition: { 
        duration: 0.3,
        ease: 'easeInOut' 
      } 
    }
  };

  return (
    <motion.div
      className={`relative rounded-lg shadow-sm border ${
        theme === 'dark' 
          ? 'bg-gray-800 border-gray-700' 
          : 'bg-white border-gray-200'
      } overflow-hidden transition-all duration-200 ${
        isExpanded ? 'col-span-2 row-span-2 z-10' : ''
      }`}
      variants={widgetVariants}
      initial="hidden"
      animate="visible"
      exit="exit"
      layout
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      {/* Widget Header */}
      <div 
        className={`px-4 py-3 flex items-center justify-between cursor-pointer transition-colors ${
          theme === 'dark' 
            ? 'bg-gray-700 hover:bg-gray-600' 
            : 'bg-gray-50 hover:bg-gray-50/80'
        }`}
        onClick={toggleExpand}
      >
        <h3 className={`text-sm font-medium ${
          theme === 'dark' ? 'text-white' : 'text-gray-900'
        }`}>
          {widget.title}
        </h3>
        
        <div className="flex items-center space-x-2">
          {isExpanded && (
            <button
              type="button"
              onClick={(e) => {
                e.stopPropagation();
                toggleExpand();
              }}
              className={`p-1 rounded-full ${
                theme === 'dark' 
                  ? 'text-gray-300 hover:bg-gray-500' 
                  : 'text-gray-500 hover:bg-gray-200'
              }`}
              title="Minimize"
            >
              <ArrowsPointingOutIcon className="h-4 w-4 transform rotate-45" />
            </button>
          )}
          
          {!isExpanded && (
            <button
              type="button"
              onClick={(e) => {
                e.stopPropagation();
                toggleExpand();
              }}
              className={`p-1 rounded-full ${
                theme === 'dark' 
                  ? 'text-gray-300 hover:bg-gray-500' 
                  : 'text-gray-500 hover:bg-gray-200'
              }`}
              title="Expand"
            >
              <ArrowsPointingOutIcon className="h-4 w-4" />
            </button>
          )}
          
          {isEditing && (
            <>
              <button
                type="button"
                onClick={handleEdit}
                className={`p-1 rounded-full ${
                  theme === 'dark' 
                    ? 'text-blue-400 hover:bg-gray-500' 
                    : 'text-blue-600 hover:bg-blue-50'
                }`}
                title="Edit widget settings"
              >
                <PencilSquareIcon className="h-4 w-4" />
              </button>
              
              <button
                type="button"
                onClick={handleRemove}
                className={`p-1 rounded-full ${
                  theme === 'dark' 
                    ? 'text-red-400 hover:bg-gray-500' 
                    : 'text-red-600 hover:bg-red-50'
                }`}
                title="Remove widget"
              >
                <XMarkIcon className="h-4 w-4" />
              </button>
            </>
          )}
        </div>
      </div>
      
      {/* Widget Content */}
      <AnimatePresence initial={false}>
        <motion.div
          className="overflow-hidden"
          variants={contentVariants}
          initial="expanded"
          animate={isExpanded ? 'expanded' : 'collapsed'}
          exit="collapsed"
        >
          <div 
            ref={contentRef}
            className={`p-4 ${theme === 'dark' ? 'text-gray-200' : 'text-gray-700'}`}
          >
            {children}
          </div>
        </motion.div>
      </AnimatePresence>
      
      {/* Resize handle */}
      {isHovered && (
        <div className="absolute bottom-0 right-0 w-3 h-3 cursor-se-resize">
          <div className={`w-full h-full border-r-2 border-b-2 ${
            theme === 'dark' ? 'border-gray-500' : 'border-gray-300'
          }`} />
        </div>
      )}
    </motion.div>
  );
};

export default WidgetContainer;
