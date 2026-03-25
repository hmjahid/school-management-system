import { useState, useEffect } from 'react';
import { Dialog, Transition } from '@headlessui/react';
import { Fragment } from 'react';
import { XMarkIcon, ArrowsUpDownIcon, EyeIcon, EyeSlashIcon, ArrowPathIcon } from '@heroicons/react/24/outline';
import { DragDropContext, Droppable, Draggable } from 'react-beautiful-dnd';

const WidgetSettingsModal = ({ isOpen, onClose, widgets, onSave, onReset }) => {
  const [localWidgets, setLocalWidgets] = useState([]);
  const [isDragging, setIsDragging] = useState(false);

  // Initialize local state when modal opens or widgets change
  useEffect(() => {
    if (isOpen && widgets) {
      setLocalWidgets(
        Object.values(widgets)
          .sort((a, b) => a.position - b.position)
          .map(widget => ({
            ...widget,
            // Ensure settings is always an object
            settings: widget.settings || {}
          }))
      );
    }
  }, [isOpen, widgets]);

  const handleDragEnd = (result) => {
    if (!result.destination) return;
    
    const items = Array.from(localWidgets);
    const [reorderedItem] = items.splice(result.source.index, 1);
    items.splice(result.destination.index, 0, reorderedItem);
    
    // Update positions based on new order
    const updatedItems = items.map((item, index) => ({
      ...item,
      position: index + 1
    }));
    
    setLocalWidgets(updatedItems);
    setIsDragging(false);
  };

  const toggleWidget = (widgetId) => {
    setLocalWidgets(prevWidgets =>
      prevWidgets.map(widget =>
        widget.id === widgetId
          ? { ...widget, enabled: !widget.enabled }
          : widget
      )
    );
  };

  const updateWidgetSettings = (widgetId, settings) => {
    setLocalWidgets(prevWidgets =>
      prevWidgets.map(widget =>
        widget.id === widgetId
          ? { 
              ...widget, 
              settings: { 
                ...widget.settings, 
                ...settings 
              } 
            }
          : widget
      )
    );
  };

  const handleSave = async () => {
    const widgetsObject = {};
    localWidgets.forEach(widget => {
      widgetsObject[widget.id] = widget;
    });
    await onSave(widgetsObject);
    onClose();
  };

  const handleReset = async () => {
    if (window.confirm('Are you sure you want to reset all widgets to their default settings?')) {
      const success = await onReset();
      if (success) {
        onClose();
      }
    }
  };

  return (
    <Transition appear show={isOpen} as={Fragment}>
      <Dialog as="div" className="relative z-50" onClose={onClose}>
        <Transition.Child
          as={Fragment}
          enter="ease-out duration-300"
          enterFrom="opacity-0"
          enterTo="opacity-100"
          leave="ease-in duration-200"
          leaveFrom="opacity-100"
          leaveTo="opacity-0"
        >
          <div className="fixed inset-0 bg-black/25" />
        </Transition.Child>

        <div className="fixed inset-0 overflow-y-auto">
          <div className="flex min-h-full items-center justify-center p-4 text-center">
            <Transition.Child
              as={Fragment}
              enter="ease-out duration-300"
              enterFrom="opacity-0 scale-95"
              enterTo="opacity-100 scale-100"
              leave="ease-in duration-200"
              leaveFrom="opacity-100 scale-100"
              leaveTo="opacity-0 scale-95"
            >
              <Dialog.Panel className="w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white dark:bg-gray-800 p-6 text-left align-middle shadow-xl transition-all">
                <div className="flex justify-between items-center mb-4">
                  <Dialog.Title
                    as="h3"
                    className="text-lg font-medium leading-6 text-gray-900 dark:text-white"
                  >
                    Customize Dashboard
                  </Dialog.Title>
                  <div className="flex space-x-2">
                    <button
                      type="button"
                      className="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-gray-700 dark:text-gray-200 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                      onClick={handleReset}
                    >
                      <ArrowPathIcon className="h-4 w-4 mr-1" />
                      Reset Defaults
                    </button>
                    <button
                      type="button"
                      className="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none"
                      onClick={onClose}
                    >
                      <span className="sr-only">Close</span>
                      <XMarkIcon className="h-6 w-6" aria-hidden="true" />
                    </button>
                  </div>
                </div>

                <div className="mt-2">
                  <p className="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Drag and drop to reorder widgets or toggle their visibility.
                  </p>

                  <div className="space-y-4">
                    <DragDropContext 
                      onDragStart={() => setIsDragging(true)}
                      onDragEnd={(result) => {
                        handleDragEnd(result);
                        setIsDragging(false);
                      }}
                    >
                      <Droppable droppableId="widgets">
                        {(provided) => (
                          <div 
                            {...provided.droppableProps} 
                            ref={provided.innerRef}
                            className={`space-y-2 ${isDragging ? 'opacity-75' : ''}`}
                          >
                            {localWidgets.map((widget, index) => (
                              <Draggable key={widget.id} draggableId={widget.id} index={index}>
                                {(provided, snapshot) => (
                                  <div
                                    ref={provided.innerRef}
                                    {...provided.draggableProps}
                                    className={`bg-white dark:bg-gray-700 shadow rounded-lg border ${
                                      widget.enabled 
                                        ? 'border-gray-200 dark:border-gray-600' 
                                        : 'border-gray-100 dark:border-gray-700 opacity-70'
                                    } ${snapshot.isDragging ? 'ring-2 ring-indigo-500' : ''}`}
                                  >
                                    <div className="px-4 py-3 flex items-center justify-between">
                                      <div className="flex items-center">
                                        <button
                                          type="button"
                                          {...provided.dragHandleProps}
                                          className="text-gray-400 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300 mr-3"
                                        >
                                          <ArrowsUpDownIcon className="h-5 w-5" />
                                        </button>
                                        <span className="font-medium text-gray-900 dark:text-white">
                                          {widget.title}
                                        </span>
                                      </div>
                                      <div className="flex items-center space-x-3">
                                        <button
                                          type="button"
                                          onClick={() => toggleWidget(widget.id)}
                                          className={`p-1.5 rounded-full ${
                                            widget.enabled 
                                              ? 'text-indigo-600 hover:bg-indigo-50 dark:text-indigo-400 dark:hover:bg-gray-600' 
                                              : 'text-gray-400 hover:bg-gray-100 dark:text-gray-500 dark:hover:bg-gray-600'
                                          }`}
                                        >
                                          {widget.enabled ? (
                                            <EyeIcon className="h-5 w-5" />
                                          ) : (
                                            <EyeSlashIcon className="h-5 w-5" />
                                          )}
                                        </button>
                                      </div>
                                    </div>
                                    
                                    {widget.enabled && widget.settings && Object.keys(widget.settings).length > 0 && (
                                      <div className="px-4 pb-3 pt-1 border-t border-gray-100 dark:border-gray-600 bg-gray-50 dark:bg-gray-600/30 rounded-b-lg">
                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                          {Object.entries(widget.settings).map(([key, value]) => (
                                            <div key={key} className="space-y-1">
                                              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {key.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}
                                              </label>
                                              {typeof value === 'boolean' ? (
                                                <div className="mt-1">
                                                  <label className="inline-flex items-center cursor-pointer">
                                                    <input
                                                      type="checkbox"
                                                      checked={widget.settings[key]}
                                                      onChange={(e) => 
                                                        updateWidgetSettings(widget.id, { [key]: e.target.checked })
                                                      }
                                                      className="sr-only peer"
                                                    />
                                                    <div className="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                                                  </label>
                                                </div>
                                              ) : (
                                                <input
                                                  type={typeof value === 'number' ? 'number' : 'text'}
                                                  value={widget.settings[key]}
                                                  onChange={(e) => 
                                                    updateWidgetSettings(widget.id, { 
                                                      [key]: typeof value === 'number' 
                                                        ? parseInt(e.target.value, 10) 
                                                        : e.target.value 
                                                    })
                                                  }
                                                  className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                                />
                                              )}
                                            </div>
                                          ))}
                                        </div>
                                      </div>
                                    )}
                                  </div>
                                )}
                              </Draggable>
                            ))}
                            {provided.placeholder}
                          </div>
                        )}
                      </Droppable>
                    </DragDropContext>
                  </div>
                </div>

                <div className="mt-6 flex justify-end space-x-3">
                  <button
                    type="button"
                    className="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600"
                    onClick={onClose}
                  >
                    Cancel
                  </button>
                  <button
                    type="button"
                    className="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2"
                    onClick={handleSave}
                  >
                    Save Changes
                  </button>
                </div>
              </Dialog.Panel>
            </Transition.Child>
          </div>
        </div>
      </Dialog>
    </Transition>
  );
};

export default WidgetSettingsModal;
