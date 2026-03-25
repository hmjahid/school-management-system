import React, { useState, useEffect } from 'react';
import { 
  FiCalendar, 
  FiClock, 
  FiBookOpen, 
  FiCheckCircle, 
  FiAlertCircle, 
  FiChevronDown, 
  FiChevronUp,
  FiSearch,
  FiFilter,
  FiDownload,
  FiUpload,
  FiPlus,
  FiCheck,
  FiX,
  FiLoader
} from 'react-icons/fi';
import { format, parseISO, isBefore, isAfter, isToday, isTomorrow } from 'date-fns';

const AssignmentsPage = () => {
  const [assignments, setAssignments] = useState([]);
  const [filter, setFilter] = useState('all'); // all, pending, submitted, graded, overdue
  const [searchTerm, setSearchTerm] = useState('');
  const [expandedAssignment, setExpandedAssignment] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [submissionText, setSubmissionText] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  // Sample data - replace with API call
  const sampleAssignments = [
    {
      id: 1,
      title: 'Math Homework - Chapter 5',
      subject: 'Mathematics',
      description: 'Complete exercises 1-10 from Chapter 5. Show all your work for full credit.',
      dueDate: format(new Date(), 'yyyy-MM-dd'),
      dueTime: '23:59',
      status: 'pending', // pending, submitted, graded, overdue
      submission: {
        text: '',
        submittedAt: null,
        grade: null,
        feedback: ''
      },
      attachments: [
        { id: 1, name: 'chapter5_exercises.pdf', type: 'pdf', size: '2.4 MB' },
        { id: 2, name: 'chapter5_notes.pdf', type: 'pdf', size: '1.2 MB' }
      ]
    },
    {
      id: 2,
      title: 'Science Project Proposal',
      subject: 'Science',
      description: 'Submit a 1-page proposal for your science fair project. Include your research question, hypothesis, and methodology.',
      dueDate: format(addDays(new Date(), 2), 'yyyy-MM-dd'),
      dueTime: '09:00',
      status: 'pending',
      submission: {
        text: '',
        submittedAt: null,
        grade: null,
        feedback: ''
      },
      attachments: [
        { id: 3, name: 'science_fair_guidelines.pdf', type: 'pdf', size: '1.8 MB' }
      ]
    },
    {
      id: 3,
      title: 'Book Report - To Kill a Mockingbird',
      subject: 'English',
      description: 'Write a 3-page book report on To Kill a Mockingbird. Include analysis of themes, characters, and your personal reflection.',
      dueDate: format(subDays(new Date(), 1), 'yyyy-MM-dd'),
      dueTime: '23:59',
      status: 'overdue',
      submission: {
        text: '',
        submittedAt: null,
        grade: null,
        feedback: ''
      },
      attachments: [
        { id: 4, name: 'to_kill_a_mockingbird_guidelines.pdf', type: 'pdf', size: '0.8 MB' }
      ]
    },
    {
      id: 4,
      title: 'History Essay - World War II',
      subject: 'History',
      description: 'Write a 5-page essay on the causes and consequences of World War II.',
      dueDate: format(addDays(new Date(), 5), 'yyyy-MM-dd'),
      dueTime: '23:59',
      status: 'submitted',
      submission: {
        text: 'World War II was a global war that lasted from 1939 to 1945...',
        submittedAt: format(subDays(new Date(), 2), "yyyy-MM-dd'T'HH:mm:ss"),
        grade: null,
        feedback: ''
      },
      attachments: [
        { id: 5, name: 'wwii_essay_guidelines.pdf', type: 'pdf', size: '1.1 MB' },
        { id: 6, name: 'my_essay.docx', type: 'docx', size: '0.2 MB' }
      ]
    },
    {
      id: 5,
      title: 'Math Quiz - Algebra',
      subject: 'Mathematics',
      description: 'Complete the algebra quiz covering chapters 1-4.',
      dueDate: format(subDays(new Date(), 7), 'yyyy-MM-dd'),
      dueTime: '23:59',
      status: 'graded',
      submission: {
        text: '1. x = 5\n2. y = 3x + 2\n...',
        submittedAt: format(subDays(new Date(), 8), "yyyy-MM-dd'T'HH:mm:ss"),
        grade: 'A-',
        feedback: 'Great work! You showed a strong understanding of algebraic concepts. Pay attention to sign changes when solving equations.'
      },
      attachments: [
        { id: 7, name: 'algebra_quiz_answers.pdf', type: 'pdf', size: '0.5 MB' }
      ]
    }
  ];

  // Helper function to add days to a date
  function addDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
  }

  // Helper function to subtract days from a date
  function subDays(date, days) {
    const result = new Date(date);
    result.setDate(result.getDate() - days);
    return result;
  }

  useEffect(() => {
    const fetchAssignments = async () => {
      try {
        setLoading(true);
        // TODO: Replace with actual API call
        // const response = await api.get('/api/student/assignments');
        // setAssignments(response.data);
        
        // For now, use sample data
        setAssignments(sampleAssignments);
        setError(null);
      } catch (err) {
        console.error('Error fetching assignments:', err);
        setError('Failed to load assignments. Please try again later.');
      } finally {
        setLoading(false);
      }
    };

    fetchAssignments();
  }, []);

  const filteredAssignments = assignments.filter(assignment => {
    // Filter by status
    if (filter !== 'all' && assignment.status !== filter) {
      return false;
    }
    
    // Filter by search term
    if (searchTerm) {
      const searchLower = searchTerm.toLowerCase();
      return (
        assignment.title.toLowerCase().includes(searchLower) ||
        assignment.subject.toLowerCase().includes(searchLower) ||
        assignment.description.toLowerCase().includes(searchLower)
      );
    }
    
    return true;
  });

  const handleSubmitAssignment = async (assignmentId) => {
    if (!submissionText.trim()) {
      alert('Please enter your submission text');
      return;
    }
    
    try {
      setIsSubmitting(true);
      // TODO: Replace with actual API call
      // await api.post(`/api/assignments/${assignmentId}/submit`, {
      //   text: submissionText,
      //   submittedAt: new Date().toISOString()
      // });
      
      // Update local state
      setAssignments(prev => 
        prev.map(a => 
          a.id === assignmentId 
            ? { 
                ...a, 
                status: 'submitted',
                submission: {
                  ...a.submission,
                  text: submissionText,
                  submittedAt: new Date().toISOString()
                }
              } 
            : a
        )
      );
      
      setSubmissionText('');
      setExpandedAssignment(null);
      toast.success('Assignment submitted successfully!');
    } catch (err) {
      console.error('Error submitting assignment:', err);
      toast.error('Failed to submit assignment. Please try again.');
    } finally {
      setIsSubmitting(false);
    }
  };

  const handleDownloadAttachment = (attachment) => {
    // TODO: Implement actual file download
    console.log('Downloading:', attachment.name);
    // This would typically be a link to the actual file
    // window.open(`/api/attachments/${attachment.id}/download`, '_blank');
  };

  const getStatusBadge = (status, dueDate) => {
    const baseStyles = 'px-2 py-1 text-xs font-medium rounded-full';
    
    switch (status) {
      case 'submitted':
        return (
          <span className={`${baseStyles} bg-blue-100 text-blue-800`}>
            Submitted
          </span>
        );
      case 'graded':
        return (
          <span className={`${baseStyles} bg-green-100 text-green-800`}>
            Graded
          </span>
        );
      case 'overdue':
        return (
          <span className={`${baseStyles} bg-red-100 text-red-800`}>
            Overdue
          </span>
        );
      case 'pending':
        const due = parseISO(`${dueDate}T23:59:59`);
        if (isBefore(due, new Date())) {
          return (
            <span className={`${baseStyles} bg-red-100 text-red-800`}>
              Overdue
            </span>
          );
        } else if (isToday(due)) {
          return (
            <span className={`${baseStyles} bg-yellow-100 text-yellow-800`}>
              Due Today
            </span>
          );
        } else if (isTomorrow(due)) {
          return (
            <span className={`${baseStyles} bg-yellow-50 text-yellow-700`}>
              Due Tomorrow
            </span>
          );
        } else {
          return (
            <span className={`${baseStyles} bg-gray-100 text-gray-800`}>
              Pending
            </span>
          );
        }
      default:
        return (
          <span className={`${baseStyles} bg-gray-100 text-gray-800`}>
            {status}
          </span>
        );
    }
  };

  const getDueDateText = (dueDate, dueTime) => {
    const date = parseISO(`${dueDate}T${dueTime}:00`);
    const now = new Date();
    const diffInDays = Math.ceil((date - now) / (1000 * 60 * 60 * 24));
    
    if (isToday(date)) {
      return `Today at ${format(date, 'h:mm a')}`;
    } else if (isTomorrow(date)) {
      return `Tomorrow at ${format(date, 'h:mm a')}`;
    } else if (diffInDays < 7 && diffInDays > 0) {
      return `This ${format(date, 'EEEE')} at ${format(date, 'h:mm a')}`;
    } else if (diffInDays < 0) {
      return `Due ${format(date, 'MMM d, yyyy')} at ${format(date, 'h:mm a')}`;
    } else {
      return format(date, 'MMM d, yyyy, h:mm a');
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-64">
        <FiLoader className="animate-spin h-10 w-10 text-indigo-600 mb-4" />
        <p className="text-gray-600">Loading assignments...</p>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border-l-4 border-red-400 p-4 mb-6">
        <div className="flex">
          <div className="flex-shrink-0">
            <FiAlertCircle className="h-5 w-5 text-red-400" />
          </div>
          <div className="ml-3">
            <p className="text-sm text-red-700">{error}</p>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="p-6">
      <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-800 mb-4 md:mb-0">My Assignments</h1>
        <div className="flex items-center space-x-2">
          <button
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
            onClick={() => {}}
          >
            <FiDownload className="-ml-1 mr-2 h-4 w-4" />
            Export
          </button>
        </div>
      </div>

      {/* Filters and Search */}
      <div className="bg-white rounded-lg shadow p-4 mb-6">
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label htmlFor="filter" className="block text-sm font-medium text-gray-700 mb-1">
              Filter by Status
            </label>
            <select
              id="filter"
              className="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              value={filter}
              onChange={(e) => setFilter(e.target.value)}
            >
              <option value="all">All Assignments</option>
              <option value="pending">Pending</option>
              <option value="submitted">Submitted</option>
              <option value="graded">Graded</option>
              <option value="overdue">Overdue</option>
            </select>
          </div>
          <div>
            <label htmlFor="search" className="block text-sm font-medium text-gray-700 mb-1">
              Search Assignments
            </label>
            <div className="relative">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <FiSearch className="h-5 w-5 text-gray-400" />
              </div>
              <input
                id="search"
                type="text"
                className="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                placeholder="Search by title, subject, or description"
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
              />
            </div>
          </div>
        </div>
      </div>

      {/* Assignments List */}
      <div className="space-y-4">
        {filteredAssignments.length === 0 ? (
          <div className="text-center py-12 bg-white rounded-lg shadow">
            <FiBookOpen className="mx-auto h-12 w-12 text-gray-400" />
            <h3 className="mt-2 text-sm font-medium text-gray-900">No assignments found</h3>
            <p className="mt-1 text-sm text-gray-500">
              {searchTerm 
                ? 'No assignments match your search. Try a different term.'
                : 'You have no assignments matching the selected filter.'}
            </p>
          </div>
        ) : (
          filteredAssignments.map((assignment) => (
            <div key={assignment.id} className="bg-white rounded-lg shadow overflow-hidden">
              <div className="p-4">
                <div className="flex items-start justify-between">
                  <div>
                    <div className="flex items-center">
                      <h3 className="text-lg font-medium text-gray-900">{assignment.title}</h3>
                      <span className="ml-2">
                        {getStatusBadge(assignment.status, assignment.dueDate)}
                      </span>
                    </div>
                    <p className="mt-1 text-sm text-gray-500">
                      {assignment.subject} • Due {getDueDateText(assignment.dueDate, assignment.dueTime)}
                    </p>
                  </div>
                  <button
                    onClick={() => 
                      setExpandedAssignment(expandedAssignment === assignment.id ? null : assignment.id)
                    }
                    className="ml-4 p-1 rounded-full text-gray-400 hover:text-gray-500 focus:outline-none"
                  >
                    {expandedAssignment === assignment.id ? (
                      <FiChevronUp className="h-5 w-5" />
                    ) : (
                      <FiChevronDown className="h-5 w-5" />
                    )}
                  </button>
                </div>

                {expandedAssignment === assignment.id && (
                  <div className="mt-4 pt-4 border-t border-gray-200">
                    <div className="prose prose-sm max-w-none">
                      <h4 className="text-sm font-medium text-gray-900 mb-2">Description</h4>
                      <p className="text-gray-700">{assignment.description}</p>
                    </div>

                    {assignment.attachments && assignment.attachments.length > 0 && (
                      <div className="mt-4">
                        <h4 className="text-sm font-medium text-gray-900 mb-2">Attachments</h4>
                        <ul className="space-y-2">
                          {assignment.attachments.map((file) => (
                            <li key={file.id} className="flex items-center">
                              <span className="text-sm text-gray-500">{file.name}</span>
                              <span className="ml-2 text-xs text-gray-400">({file.size})</span>
                              <button
                                onClick={() => handleDownloadAttachment(file)}
                                className="ml-auto text-sm text-indigo-600 hover:text-indigo-800"
                              >
                                Download
                              </button>
                            </li>
                          ))}
                        </ul>
                      </div>
                    )}

                    {assignment.submission && assignment.submission.submittedAt ? (
                      <div className="mt-4 pt-4 border-t border-gray-200">
                        <h4 className="text-sm font-medium text-gray-900 mb-2">Your Submission</h4>
                        <div className="bg-gray-50 p-3 rounded-md">
                          <p className="whitespace-pre-line text-sm text-gray-700">
                            {assignment.submission.text}
                          </p>
                        </div>
                        <p className="mt-2 text-xs text-gray-500">
                          Submitted on {format(new Date(assignment.submission.submittedAt), 'MMM d, yyyy h:mm a')}
                        </p>
                        
                        {assignment.submission.grade && (
                          <div className="mt-4 p-3 bg-blue-50 rounded-md">
                            <div className="flex items-center">
                              <span className="font-medium text-gray-900">Grade: </span>
                              <span className="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                {assignment.submission.grade}
                              </span>
                            </div>
                            {assignment.submission.feedback && (
                              <div className="mt-2">
                                <p className="text-sm font-medium text-gray-900">Feedback:</p>
                                <p className="mt-1 text-sm text-gray-700">
                                  {assignment.submission.feedback}
                                </p>
                              </div>
                            )}
                          </div>
                        )}
                      </div>
                    ) : (
                      <div className="mt-4 pt-4 border-t border-gray-200">
                        <h4 className="text-sm font-medium text-gray-900 mb-2">
                          {assignment.status === 'overdue' ? 'Late Submission' : 'Submit Assignment'}
                        </h4>
                        <textarea
                          rows={4}
                          className="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                          placeholder="Enter your submission here..."
                          value={submissionText}
                          onChange={(e) => setSubmissionText(e.target.value)}
                        />
                        <div className="mt-2 flex justify-end space-x-2">
                          <button
                            type="button"
                            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            onClick={() => setExpandedAssignment(null)}
                          >
                            Cancel
                          </button>
                          <button
                            type="button"
                            className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                            onClick={() => handleSubmitAssignment(assignment.id)}
                            disabled={!submissionText.trim() || isSubmitting}
                          >
                            {isSubmitting ? (
                              <>
                                <FiLoader className="animate-spin -ml-1 mr-2 h-4 w-4" />
                                Submitting...
                              </>
                            ) : (
                              'Submit Assignment'
                            )}
                          </button>
                        </div>
                      </div>
                    )}
                  </div>
                )}
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};

export default AssignmentsPage;
