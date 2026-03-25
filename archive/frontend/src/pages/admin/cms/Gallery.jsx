import React, { useState } from 'react';
import { FiImage, FiPlus, FiTrash2, FiEdit2, FiFolderPlus } from 'react-icons/fi';

const Gallery = () => {
  const [albums, setAlbums] = useState([
    { id: 1, name: 'Annual Day 2023', count: 24, cover: 'https://via.placeholder.com/300x200' },
    { id: 2, name: 'Sports Day', count: 18, cover: 'https://via.placeholder.com/300x200' },
    { id: 3, name: 'Science Fair', count: 15, cover: 'https://via.placeholder.com/300x200' },
  ]);

  const [showNewAlbumModal, setShowNewAlbumModal] = useState(false);
  const [newAlbumName, setNewAlbumName] = useState('');

  const createNewAlbum = () => {
    if (!newAlbumName.trim()) return;
    
    const newAlbum = {
      id: albums.length + 1,
      name: newAlbumName,
      count: 0,
      cover: 'https://via.placeholder.com/300x200'
    };
    
    setAlbums([...albums, newAlbum]);
    setNewAlbumName('');
    setShowNewAlbumModal(false);
  };

  return (
    <div className="p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Gallery</h1>
        <div className="mt-4 md:mt-0">
          <button
            onClick={() => setShowNewAlbumModal(true)}
            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiFolderPlus className="-ml-1 mr-2 h-5 w-5" />
            New Album
          </button>
        </div>
      </div>

      {/* Albums Grid */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        {albums.map((album) => (
          <div key={album.id} className="bg-white rounded-lg shadow overflow-hidden">
            <div className="relative pb-2/3">
              <img
                className="w-full h-48 object-cover"
                src={album.cover}
                alt={album.name}
              />
              <div className="absolute top-2 right-2 flex space-x-1">
                <button className="p-1 bg-white rounded-full shadow-md text-indigo-600 hover:bg-indigo-50">
                  <FiEdit2 className="h-4 w-4" />
                </button>
                <button className="p-1 bg-white rounded-full shadow-md text-red-600 hover:bg-red-50">
                  <FiTrash2 className="h-4 w-4" />
                </button>
              </div>
            </div>
            <div className="p-4">
              <h3 className="text-lg font-medium text-gray-900">{album.name}</h3>
              <p className="mt-1 text-sm text-gray-500">{album.count} photos</p>
            </div>
          </div>
        ))}
      </div>

      {/* New Album Modal */}
      {showNewAlbumModal && (
        <div className="fixed z-10 inset-0 overflow-y-auto">
          <div className="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div className="fixed inset-0 transition-opacity" aria-hidden="true">
              <div className="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span className="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">
              &#8203;
            </span>

            <div className="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
              <div>
                <div className="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100">
                  <FiFolderPlus className="h-6 w-6 text-indigo-600" />
                </div>
                <div className="mt-3 text-center sm:mt-5">
                  <h3 className="text-lg leading-6 font-medium text-gray-900">
                    Create New Album
                  </h3>
                  <div className="mt-4">
                    <input
                      type="text"
                      className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md p-2 border"
                      placeholder="Album name"
                      value={newAlbumName}
                      onChange={(e) => setNewAlbumName(e.target.value)}
                      onKeyPress={(e) => e.key === 'Enter' && createNewAlbum()}
                    />
                  </div>
                </div>
              </div>
              <div className="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                <button
                  type="button"
                  className="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm"
                  onClick={createNewAlbum}
                >
                  Create
                </button>
                <button
                  type="button"
                  className="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm"
                  onClick={() => setShowNewAlbumModal(false)}
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};

export default Gallery;
