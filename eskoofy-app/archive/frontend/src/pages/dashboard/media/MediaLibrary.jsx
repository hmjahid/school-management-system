import React from 'react';

// Simple placeholder MediaLibrary component to satisfy imports and basic UX
// Props:
// - mode: string (e.g. 'featuredImage')
// - onClose: () => void
// - onSelect: (media) => void where media is { id, url, title }

const sampleMedia = [
  { id: 1, url: '/assets/sample-image-1.jpg', title: 'Sample Image 1' },
  { id: 2, url: '/assets/sample-image-2.jpg', title: 'Sample Image 2' },
  { id: 3, url: '/assets/sample-image-3.jpg', title: 'Sample Image 3' },
];

export default function MediaLibrary({ mode, onClose, onSelect }) {
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center">
      <div className="absolute inset-0 bg-black opacity-40" onClick={onClose} />
      <div className="bg-white rounded shadow-lg max-w-2xl w-full p-4 z-10">
        <div className="flex items-center justify-between mb-4">
          <h3 className="text-lg font-medium">Media Library</h3>
          <div>
            <button className="px-3 py-1 text-sm text-gray-600" onClick={onClose}>Close</button>
          </div>
        </div>
        <div className="grid grid-cols-3 gap-4">
          {sampleMedia.map((m) => (
            <div key={m.id} className="cursor-pointer" onClick={() => onSelect && onSelect(m)}>
              <div className="h-28 w-full bg-gray-100 rounded overflow-hidden flex items-center justify-center">
                <img src={m.url} alt={m.title} className="object-cover h-full w-full" />
              </div>
              <p className="text-sm mt-2 text-center text-gray-600">{m.title}</p>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}
