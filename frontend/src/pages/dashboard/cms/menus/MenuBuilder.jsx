import React, { useState, useEffect, useCallback } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { 
  FiPlus, 
  FiTrash2, 
  FiEdit2, 
  FiChevronDown, 
  FiChevronRight,
  FiMenu,
  FiLink,
  FiSave,
  FiX,
  FiExternalLink
} from 'react-icons/fi';
import { DndProvider, useDrag, useDrop } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { cmsService } from '../../../../services/cmsService';
import LoadingSpinner from '../../../../components/common/LoadingSpinner';
import ConfirmationModal from '../../../../components/common/ConfirmationModal';

// Menu Item Component
const MenuItem = ({ 
  item, 
  index, 
  moveItem, 
  onEdit, 
  onDelete,
  isExpanded,
  toggleExpand,
  level = 0
}) => {
  const [{ isDragging }, drag] = useDrag({
    type: 'MENU_ITEM',
    item: { id: item.id, index, parentId: item.parentId },
    collect: (monitor) => ({
      isDragging: monitor.isDragging(),
    }),
  });

  const [, drop] = useDrop({
    accept: 'MENU_ITEM',
    hover(draggedItem) {
      if (draggedItem.id === item.id) return;
      if (draggedItem.parentId !== item.parentId) return;
      
      moveItem(draggedItem.index, index, draggedItem.parentId);
      draggedItem.index = index;
    },
  });

  const hasChildren = item.children && item.children.length > 0;
  const paddingLeft = 20 * level;

  return (
    <div 
      ref={(node) => drag(drop(node))}
      className={`border rounded-md mb-1 ${isDragging ? 'opacity-50' : 'opacity-100'}`}
      style={{ paddingLeft: `${paddingLeft}px` }}
    >
      <div className="flex items-center justify-between p-2 bg-white hover:bg-gray-50">
        <div className="flex items-center flex-1">
          <button 
            type="button" 
            onClick={() => toggleExpand(item.id)}
            className="mr-2 text-gray-400 hover:text-gray-600"
            disabled={!hasChildren}
          >
            {hasChildren ? (
              isExpanded ? <FiChevronDown /> : <FiChevronRight />
            ) : (
              <span className="w-4"></span>
            )}
          </button>
          <FiMenu className="mr-2 text-gray-400" />
          <span className="font-medium">{item.title}</span>
          {item.url && (
            <a 
              href={item.url} 
              target="_blank" 
              rel="noopener noreferrer"
              className="ml-2 text-blue-500 hover:text-blue-700"
              onClick={(e) => e.stopPropagation()}
            >
              <FiExternalLink className="h-3 w-3" />
            </a>
          )}
        </div>
        <div className="flex space-x-2">
          <button
            type="button"
            onClick={() => onEdit(item)}
            className="text-blue-600 hover:text-blue-800"
            title="Edit"
          >
            <FiEdit2 className="h-4 w-4" />
          </button>
          <button
            type="button"
            onClick={() => onDelete(item)}
            className="text-red-600 hover:text-red-800"
            title="Delete"
          >
            <FiTrash2 className="h-4 w-4" />
          </button>
        </div>
      </div>
      
      {hasChildren && isExpanded && (
        <div className="pl-4">
          {item.children.map((child, i) => (
            <MenuItem
              key={child.id}
              item={child}
              index={i}
              moveItem={moveItem}
              onEdit={onEdit}
              onDelete={onDelete}
              isExpanded={isExpanded}
              toggleExpand={toggleExpand}
              level={level + 1}
            />
          ))}
        </div>
      )}
    </div>
  );
};

// Menu Item Form Component
const MenuItemForm = ({ item, onSave, onCancel, isEditing }) => {
  const [formData, setFormData] = useState({
    title: '',
    url: '',
    target: '_self',
    type: 'custom',
    parentId: null,
    ...item
  });

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    onSave(formData);
  };

  return (
    <div className="bg-white p-4 rounded-lg shadow-md mb-4">
      <h3 className="text-lg font-medium mb-4">
        {isEditing ? 'Edit Menu Item' : 'Add New Menu Item'}
      </h3>
      <form onSubmit={handleSubmit}>
        <div className="grid grid-cols-1 gap-y-4">
          <div>
            <label htmlFor="title" className="block text-sm font-medium text-gray-700">
              Navigation Label <span className="text-red-500">*</span>
            </label>
            <input
              type="text"
              id="title"
              name="title"
              value={formData.title}
              onChange={handleChange}
              className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              required
            />
          </div>
          
          <div>
            <label htmlFor="url" className="block text-sm font-medium text-gray-700">
              URL <span className="text-red-500">*</span>
            </label>
            <div className="mt-1 flex rounded-md shadow-sm">
              <span className="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 text-sm">
                <FiLink className="h-4 w-4" />
              </span>
              <input
                type="text"
                id="url"
                name="url"
                value={formData.url}
                onChange={handleChange}
                placeholder="https://example.com or /page-slug"
                className="flex-1 min-w-0 block w-full px-3 py-2 rounded-none rounded-r-md border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                required
              />
            </div>
          </div>
          
          <div>
            <label htmlFor="target" className="block text-sm font-medium text-gray-700">
              Link Target
            </label>
            <select
              id="target"
              name="target"
              value={formData.target}
              onChange={handleChange}
              className="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
            >
              <option value="_self">Same Tab</option>
              <option value="_blank">New Tab</option>
            </select>
          </div>
          
          <div className="flex justify-end space-x-3 pt-2">
            <button
              type="button"
              onClick={onCancel}
              className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              Cancel
            </button>
            <button
              type="submit"
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <FiSave className="-ml-1 mr-2 h-4 w-4" />
              {isEditing ? 'Update' : 'Add'} Item
            </button>
          </div>
        </div>
      </form>
    </div>
  );
};

// Main Menu Builder Component
const MenuBuilder = () => {
  const queryClient = useQueryClient();
  const [expandedItems, setExpandedItems] = useState(new Set());
  const [isAdding, setIsAdding] = useState(false);
  const [editingItem, setEditingItem] = useState(null);
  const [deleteModalOpen, setDeleteModalOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [menuItems, setMenuItems] = useState([]);

  // Fetch menu items
  const { data: menuData, isLoading } = useQuery(
    ['cms-menus'],
    cmsService.getMenus,
    {
      onSuccess: (data) => {
        setMenuItems(data.items || []);
      },
      refetchOnWindowFocus: false,
    }
  );

  // Save menu items mutation
  const saveMenuMutation = useMutation(cmsService.updateMenu, {
    onSuccess: () => {
      queryClient.invalidateQueries(['cms-menus']);
      toast.success('Menu saved successfully');
    },
    onError: (error) => {
      toast.error(error.message || 'Failed to save menu');
    },
  });

  // Toggle item expansion
  const toggleExpand = useCallback((itemId) => {
    setExpandedItems(prev => {
      const newSet = new Set(prev);
      if (newSet.has(itemId)) {
        newSet.delete(itemId);
      } else {
        newSet.add(itemId);
      }
      return newSet;
    });
  }, []);

  // Move item in the menu
  const moveItem = useCallback((fromIndex, toIndex, parentId) => {
    setMenuItems(prevItems => {
      const newItems = [...prevItems];
      const parentItems = parentId 
        ? newItems.find(item => item.id === parentId)?.children || []
        : newItems;
      
      if (fromIndex < 0 || fromIndex >= parentItems.length || toIndex < 0 || toIndex >= parentItems.length) {
        return prevItems;
      }
      
      const [movedItem] = parentItems.splice(fromIndex, 1);
      parentItems.splice(toIndex, 0, movedItem);
      
      return [...newItems];
    });
  }, []);

  // Add new menu item
  const handleAddItem = (newItem) => {
    setMenuItems(prevItems => [...prevItems, { ...newItem, id: `temp-${Date.now()}`, children: [] }]);
    setIsAdding(false);
  };

  // Update existing menu item
  const handleUpdateItem = (updatedItem) => {
    const updateItemInTree = (items) => {
      return items.map(item => {
        if (item.id === updatedItem.id) {
          return { ...item, ...updatedItem };
        }
        if (item.children && item.children.length > 0) {
          return {
            ...item,
            children: updateItemInTree(item.children)
          };
        }
        return item;
      });
    };

    setMenuItems(prevItems => updateItemInTree(prevItems));
    setEditingItem(null);
  };

  // Delete menu item
  const handleDeleteItem = (itemToDelete) => {
    const deleteItemFromTree = (items) => {
      return items.reduce((acc, item) => {
        if (item.id === itemToDelete.id) {
          return acc; // Skip this item
        }
        
        if (item.children && item.children.length > 0) {
          return [
            ...acc,
            {
              ...item,
              children: deleteItemFromTree(item.children)
            }
          ];
        }
        
        return [...acc, item];
      }, []);
    };

    setMenuItems(prevItems => deleteItemFromTree(prevItems));
    setDeleteModalOpen(false);
  };

  // Save menu structure
  const handleSaveMenu = () => {
    saveMenuMutation.mutate({
      id: 'primary',
      items: menuItems
    });
  };

  // Handle drag and drop
  const handleDrop = (item, monitor, parentId = null) => {
    const dragIndex = item.index;
    const hoverIndex = -1; // Append to the end of the parent
    
    if (dragIndex === hoverIndex) return;
    
    // Update the parentId of the dragged item
    setMenuItems(prevItems => {
      const newItems = JSON.parse(JSON.stringify(prevItems));
      
      // Find and remove the dragged item
      const findAndRemove = (items, id) => {
        for (let i = 0; i < items.length; i++) {
          if (items[i].id === id) {
            const [removed] = items.splice(i, 1);
            return removed;
          }
          if (items[i].children && items[i].children.length > 0) {
            const found = findAndRemove(items[i].children, id);
            if (found) return found;
          }
        }
        return null;
      };
      
      const draggedItem = findAndRemove(newItems, item.id);
      if (!draggedItem) return prevItems;
      
      // Update parentId
      draggedItem.parentId = parentId;
      
      // Add to the new parent or root
      if (parentId) {
        const findAndAdd = (items, targetId) => {
          for (const item of items) {
            if (item.id === targetId) {
              if (!item.children) item.children = [];
              item.children.push(draggedItem);
              return true;
            }
            if (item.children && item.children.length > 0) {
              if (findAndAdd(item.children, targetId)) return true;
            }
          }
          return false;
        };
        
        findAndAdd(newItems, parentId);
      } else {
        // Add to root
        newItems.push(draggedItem);
      }
      
      return [...newItems];
    });
  };

  if (isLoading) {
    return <LoadingSpinner />;
  }

  return (
    <DndProvider backend={HTML5Backend}>
      <div className="space-y-6">
        <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
          <div>
            <h2 className="text-lg font-medium text-gray-900">Menu Builder</h2>
            <p className="mt-1 text-sm text-gray-500">
              Create and manage your website navigation menus
            </p>
          </div>
          <div className="flex space-x-3">
            <button
              type="button"
              onClick={() => setIsAdding(true)}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            >
              <FiPlus className="-ml-1 mr-2 h-4 w-4" />
              Add Item
            </button>
            <button
              type="button"
              onClick={handleSaveMenu}
              disabled={saveMenuMutation.isLoading}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
            >
              {saveMenuMutation.isLoading ? (
                <>
                  <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Saving...
                </>
              ) : (
                <>
                  <FiSave className="-ml-1 mr-2 h-4 w-4" />
                  Save Menu
                </>
              )}
            </button>
          </div>
        </div>

        {/* Add/Edit Form */}
        {(isAdding || editingItem) && (
          <MenuItemForm
            item={editingItem || { title: '', url: '', target: '_self' }}
            onSave={editingItem ? handleUpdateItem : handleAddItem}
            onCancel={() => {
              setIsAdding(false);
              setEditingItem(null);
            }}
            isEditing={!!editingItem}
          />
        )}

        {/* Menu Items List */}
        <div className="bg-white shadow overflow-hidden sm:rounded-lg">
          <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
            <h3 className="text-lg leading-6 font-medium text-gray-900">
              Menu Structure
            </h3>
            <p className="mt-1 text-sm text-gray-500">
              Drag and drop items to reorder them. Click the arrow to expand/collapse nested items.
            </p>
          </div>
          
          <div className="p-4">
            {menuItems.length === 0 ? (
              <div className="text-center py-8 text-gray-500">
                <p>No menu items yet. Click "Add Item" to get started.</p>
              </div>
            ) : (
              <div className="space-y-2">
                {menuItems.map((item, index) => (
                  <MenuItem
                    key={item.id}
                    item={item}
                    index={index}
                    moveItem={moveItem}
                    onEdit={setEditingItem}
                    onDelete={(item) => {
                      setItemToDelete(item);
                      setDeleteModalOpen(true);
                    }}
                    isExpanded={expandedItems.has(item.id)}
                    toggleExpand={toggleExpand}
                  />
                ))}
              </div>
            )}
          </div>
          
          {/* Drop target for root level */}
          <div 
            className="p-4 border-t border-dashed border-gray-300 text-center text-gray-500 text-sm"
            onDrop={(e) => {
              e.preventDefault();
              const item = JSON.parse(e.dataTransfer.getData('text/plain'));
              handleDrop(item, null, null);
            }}
            onDragOver={(e) => e.preventDefault()}
          >
            Drop items here to add to root level
          </div>
        </div>
      </div>

      {/* Delete Confirmation Modal */}
      <ConfirmationModal
        isOpen={deleteModalOpen}
        onClose={() => setDeleteModalOpen(false)}
        onConfirm={() => {
          handleDeleteItem(itemToDelete);
          setItemToDelete(null);
        }}
        title="Delete Menu Item"
        message={`Are you sure you want to delete "${itemToDelete?.title}"? This action cannot be undone.`}
        confirmText="Delete"
        cancelText="Cancel"
        isDanger={true}
      />
    </DndProvider>
  );
};

export default MenuBuilder;
