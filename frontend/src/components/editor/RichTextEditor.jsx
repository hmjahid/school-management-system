import React, { useRef, useEffect, useState } from 'react';
import { FiBold, FiItalic, FiUnderline, FiList, FiAlignLeft, FiAlignCenter, FiAlignRight, FiLink, FiImage } from 'react-icons/fi';

const RichTextEditor = ({ value, onChange, placeholder = 'Start writing...' }) => {
  const editorRef = useRef(null);
  const [isLinkModalOpen, setIsLinkModalOpen] = useState(false);
  const [linkUrl, setLinkUrl] = useState('');
  const [selection, setSelection] = useState(null);

  // Save current selection when editor loses focus
  const saveSelection = () => {
    const sel = window.getSelection();
    if (sel.rangeCount > 0) {
      setSelection(sel.getRangeAt(0));
    }
  };

  // Restore selection when editor gets focus
  const restoreSelection = () => {
    if (selection) {
      const sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(selection);
    }
  };

  // Handle text formatting
  const formatText = (command, value = null) => {
    document.execCommand(command, false, value);
    editorRef.current.focus();
    onChange(editorRef.current.innerHTML);
  };

  // Handle image upload
  const handleImageUpload = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = (event) => {
      const img = document.createElement('img');
      img.src = event.target.result;
      img.style.maxWidth = '100%';
      
      // Insert image at cursor position
      if (selection) {
        restoreSelection();
        document.execCommand('insertHTML', false, img.outerHTML);
      } else {
        editorRef.current.appendChild(img);
      }
      
      onChange(editorRef.current.innerHTML);
    };
    reader.readAsDataURL(file);
    e.target.value = ''; // Reset file input
  };

  // Handle link insertion
  const handleInsertLink = () => {
    if (!linkUrl) return;
    
    const selectedText = window.getSelection().toString();
    const linkHtml = selectedText 
      ? `<a href="${linkUrl}" target="_blank" rel="noopener noreferrer">${selectedText}</a>`
      : `<a href="${linkUrl}" target="_blank" rel="noopener noreferrer">${linkUrl}</a>`;
    
    document.execCommand('insertHTML', false, linkHtml);
    setLinkUrl('');
    setIsLinkModalOpen(false);
    editorRef.current.focus();
    onChange(editorRef.current.innerHTML);
  };

  // Handle editor content change
  const handleInput = () => {
    onChange(editorRef.current.innerHTML);
  };

  // Check if current selection has a specific format
  const hasFormat = (format) => {
    return document.queryCommandState(format);
  };

  return (
    <div className="border border-gray-300 rounded-lg overflow-hidden">
      {/* Toolbar */}
      <div className="bg-gray-50 border-b border-gray-300 p-2 flex flex-wrap gap-1">
        <button
          type="button"
          onClick={() => formatText('bold')}
          className={`p-2 rounded hover:bg-gray-200 ${hasFormat('bold') ? 'bg-gray-200' : ''}`}
          title="Bold (Ctrl+B)"
        >
          <FiBold className="w-4 h-4" />
        </button>
        <button
          type="button"
          onClick={() => formatText('italic')}
          className={`p-2 rounded hover:bg-gray-200 ${hasFormat('italic') ? 'bg-gray-200' : ''}`}
          title="Italic (Ctrl+I)"
        >
          <FiItalic className="w-4 h-4" />
        </button>
        <button
          type="button"
          onClick={() => formatText('underline')}
          className={`p-2 rounded hover:bg-gray-200 ${hasFormat('underline') ? 'bg-gray-200' : ''}`}
          title="Underline (Ctrl+U)"
        >
          <FiUnderline className="w-4 h-4" />
        </button>
        
        <div className="border-l border-gray-300 h-6 my-1 mx-1"></div>
        
        <button
          type="button"
          onClick={() => formatText('insertUnorderedList')}
          className={`p-2 rounded hover:bg-gray-200`}
          title="Bullet List"
        >
          <FiList className="w-4 h-4" />
        </button>
        
        <div className="border-l border-gray-300 h-6 my-1 mx-1"></div>
        
        <button
          type="button"
          onClick={() => formatText('justifyLeft')}
          className={`p-2 rounded hover:bg-gray-200`}
          title="Align Left"
        >
          <FiAlignLeft className="w-4 h-4" />
        </button>
        <button
          type="button"
          onClick={() => formatText('justifyCenter')}
          className={`p-2 rounded hover:bg-gray-200`}
          title="Center"
        >
          <FiAlignCenter className="w-4 h-4" />
        </button>
        <button
          type="button"
          onClick={() => formatText('justifyRight')}
          className={`p-2 rounded hover:bg-gray-200`}
          title="Align Right"
        >
          <FiAlignRight className="w-4 h-4" />
        </button>
        
        <div className="border-l border-gray-300 h-6 my-1 mx-1"></div>
        
        <button
          type="button"
          onClick={() => setIsLinkModalOpen(true)}
          className={`p-2 rounded hover:bg-gray-200`}
          title="Insert Link"
        >
          <FiLink className="w-4 h-4" />
        </button>
        
        <label 
          className="p-2 rounded hover:bg-gray-200 cursor-pointer"
          title="Insert Image"
        >
          <FiImage className="w-4 h-4" />
          <input 
            type="file" 
            className="hidden" 
            accept="image/*" 
            onChange={handleImageUpload}
          />
        </label>
      </div>
      
      {/* Editor */}
      <div
        ref={editorRef}
        className="min-h-[200px] p-4 outline-none"
        contentEditable
        dangerouslySetInnerHTML={{ __html: value }}
        onInput={handleInput}
        onBlur={saveSelection}
        onFocus={restoreSelection}
        placeholder={placeholder}
      />
      
      {/* Link Modal */}
      {isLinkModalOpen && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
          <div className="bg-white rounded-lg p-6 w-96">
            <h3 className="text-lg font-medium mb-4">Insert Link</h3>
            <input
              type="text"
              value={linkUrl}
              onChange={(e) => setLinkUrl(e.target.value)}
              placeholder="Enter URL"
              className="w-full p-2 border border-gray-300 rounded mb-4"
              onKeyPress={(e) => e.key === 'Enter' && handleInsertLink()}
            />
            <div className="flex justify-end space-x-2">
              <button
                type="button"
                onClick={() => {
                  setLinkUrl('');
                  setIsLinkModalOpen(false);
                }}
                className="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
              >
                Cancel
              </button>
              <button
                type="button"
                onClick={handleInsertLink}
                className="px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700"
              >
                Insert
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default RichTextEditor;
