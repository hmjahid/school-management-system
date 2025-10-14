import React from 'react';
import { FiCalendar, FiPlus, FiEdit2, FiTrash2, FiSearch, FiClock, FiMapPin } from 'react-icons/fi';

const Events = () => {
  // Sample data - replace with actual API call
  const events = [
    { 
      id: 1, 
      title: 'Annual Sports Day', 
      date: '2023-07-15',
      time: '09:00 AM - 02:00 PM',
      location: 'School Ground',
      status: 'Upcoming'
    },
    { 
      id: 2, 
      title: 'Parent-Teacher Meeting', 
      date: '2023-07-20',
      time: '10:00 AM - 01:00 PM',
      location: 'Main Hall',
      status: 'Upcoming'
    },
  ];

  return (
    <div className="p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800">Events</h1>
        <div className="mt-4 md:mt-0">
          <button
            type="button"
            className="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiPlus className="-ml-1 mr-2 h-5 w-5" />
            Add Event
          </button>
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-md">
        <ul className="divide-y divide-gray-200">
          {events.map((event) => (
            <li key={event.id}>
              <div className="px-4 py-4 sm:px-6">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center">
                      <div className="flex-shrink-0 bg-indigo-100 p-3 rounded-lg">
                        <FiCalendar className="h-6 w-6 text-indigo-600" />
                      </div>
                      <div className="ml-4">
                        <h3 className="text-lg font-medium text-gray-900">{event.title}</h3>
                        <div className="mt-1 text-sm text-gray-500">
                          <div className="flex items-center">
                            <FiCalendar className="mr-1.5 h-4 w-4 text-gray-400" />
                            {event.date}
                            <FiClock className="ml-3 mr-1.5 h-4 w-4 text-gray-400" />
                            {event.time}
                            <FiMapPin className="ml-3 mr-1.5 h-4 w-4 text-gray-400" />
                            {event.location}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="ml-4 flex-shrink-0 flex space-x-2">
                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                      {event.status}
                    </span>
                    <button className="text-indigo-600 hover:text-indigo-900">
                      <FiEdit2 className="h-5 w-5" />
                    </button>
                    <button className="text-red-600 hover:text-red-900">
                      <FiTrash2 className="h-5 w-5" />
                    </button>
                  </div>
                </div>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

export default Events;
