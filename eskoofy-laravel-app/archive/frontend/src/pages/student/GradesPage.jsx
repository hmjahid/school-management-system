import React, { useState, useEffect } from 'react';
import { 
  FiBookOpen, 
  FiAward, 
  FiTrendingUp, 
  FiFilter, 
  FiDownload,
  FiLoader,
  FiAlertCircle,
  FiBarChart2,
  FiPieChart
} from 'react-icons/fi';

const GradesPage = () => {
  const [selectedSubject, setSelectedSubject] = useState('all');
  const [selectedTerm, setSelectedTerm] = useState('all');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [grades, setGrades] = useState([]);
  const [subjects, setSubjects] = useState([]);
  const [terms, setTerms] = useState([]);
  const [showChart, setShowChart] = useState('bar'); // 'bar' or 'pie'

  // Sample data - replace with API call
  const sampleData = {
    subjects: [
      { id: 'math', name: 'Mathematics' },
      { id: 'physics', name: 'Physics' },
      { id: 'chemistry', name: 'Chemistry' },
      { id: 'biology', name: 'Biology' },
    ],
    terms: [
      { id: 'term1', name: 'Term 1' },
      { id: 'term2', name: 'Term 2' },
      { id: 'final', name: 'Final' },
    ],
    grades: [
      { 
        id: 1, 
        subject: 'math', 
        term: 'term1',
        score: 92, 
        grade: 'A',
        maxScore: 100,
        average: 78,
        rank: 5,
        totalStudents: 30
      },
      { 
        id: 2, 
        subject: 'physics', 
        term: 'term1',
        score: 88, 
        grade: 'B+',
        maxScore: 100,
        average: 82,
        rank: 8,
        totalStudents: 30
      },
      { 
        id: 3, 
        subject: 'chemistry', 
        term: 'term1',
        score: 95, 
        grade: 'A',
        maxScore: 100,
        average: 75,
        rank: 3,
        totalStudents: 30
      },
      { 
        id: 4, 
        subject: 'biology', 
        term: 'term1',
        score: 85, 
        grade: 'B+',
        maxScore: 100,
        average: 80,
        rank: 10,
        totalStudents: 30
      },
    ]
  };

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        // TODO: Replace with actual API calls
        // const [gradesRes, subjectsRes, termsRes] = await Promise.all([
        //   api.get('/api/student/grades'),
        //   api.get('/api/subjects'),
        //   api.get('/api/terms')
        // ]);
        
        // For now, use sample data
        setGrades(sampleData.grades);
        setSubjects(sampleData.subjects);
        setTerms(sampleData.terms);
        setError(null);
      } catch (err) {
        console.error('Error fetching grades:', err);
        setError('Failed to load grades. Please try again later.');
      } finally {
        setLoading(false);
      }
    };

    fetchData();
  }, []);

  const filteredGrades = grades.filter(grade => {
    const subjectMatch = selectedSubject === 'all' || grade.subject === selectedSubject;
    const termMatch = selectedTerm === 'all' || grade.term === selectedTerm;
    return subjectMatch && termMatch;
  });

  const calculateGPA = () => {
    if (filteredGrades.length === 0) return 0;
    const total = filteredGrades.reduce((sum, grade) => {
      // Simple GPA calculation (A=4, B=3, etc.)
      const gradePoints = {
        'A+': 4.0, 'A': 4.0, 'A-': 3.7,
        'B+': 3.3, 'B': 3.0, 'B-': 2.7,
        'C+': 2.3, 'C': 2.0, 'C-': 1.7,
        'D+': 1.3, 'D': 1.0, 'F': 0.0
      };
      return sum + (gradePoints[grade.grade] || 0);
    }, 0);
    return (total / filteredGrades.length).toFixed(2);
  };

  const getSubjectName = (subjectId) => {
    const subject = subjects.find(s => s.id === subjectId);
    return subject ? subject.name : subjectId;
  };

  const getTermName = (termId) => {
    const term = terms.find(t => t.id === termId);
    return term ? term.name : termId;
  };

  const handleExport = () => {
    // TODO: Implement export functionality
    console.log('Exporting grades...');
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center h-64">
        <FiLoader className="animate-spin h-10 w-10 text-indigo-600 mb-4" />
        <p className="text-gray-600">Loading grades...</p>
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
        <h1 className="text-2xl font-bold text-gray-800 mb-4 md:mb-0">My Grades</h1>
        <div className="flex items-center space-x-2">
          <button
            onClick={() => setShowChart(showChart === 'bar' ? 'pie' : 'bar')}
            className="p-2 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-50"
            title={showChart === 'bar' ? 'Show Pie Chart' : 'Show Bar Chart'}
          >
            {showChart === 'bar' ? (
              <FiPieChart className="h-5 w-5" />
            ) : (
              <FiBarChart2 className="h-5 w-5" />
            )}
          </button>
          <button
            onClick={handleExport}
            className="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <FiDownload className="-ml-1 mr-2 h-4 w-4" />
            Export
          </button>
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div className="bg-white p-4 rounded-lg shadow">
          <div className="flex items-center">
            <div className="p-3 rounded-full bg-indigo-100 text-indigo-600">
              <FiAward className="h-6 w-6" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Current GPA</p>
              <p className="text-2xl font-semibold text-gray-900">{calculateGPA()}</p>
            </div>
          </div>
        </div>
        <div className="bg-white p-4 rounded-lg shadow">
          <div className="flex items-center">
            <div className="p-3 rounded-full bg-green-100 text-green-600">
              <FiTrendingUp className="h-6 w-6" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Best Subject</p>
              <p className="text-lg font-semibold text-gray-900">
                {filteredGrades.length > 0 
                  ? getSubjectName(filteredGrades[0].subject) 
                  : 'N/A'}
              </p>
            </div>
          </div>
        </div>
        <div className="bg-white p-4 rounded-lg shadow">
          <div className="flex items-center">
            <div className="p-3 rounded-full bg-blue-100 text-blue-600">
              <FiBookOpen className="h-6 w-6" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-500">Total Subjects</p>
              <p className="text-2xl font-semibold text-gray-900">
                {new Set(filteredGrades.map(g => g.subject)).size}
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label htmlFor="subject" className="block text-sm font-medium text-gray-700 mb-1">
              Subject
            </label>
            <select
              id="subject"
              className="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              value={selectedSubject}
              onChange={(e) => setSelectedSubject(e.target.value)}
            >
              <option value="all">All Subjects</option>
              {subjects.map((subject) => (
                <option key={subject.id} value={subject.id}>
                  {subject.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label htmlFor="term" className="block text-sm font-medium text-gray-700 mb-1">
              Term
            </label>
            <select
              id="term"
              className="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              value={selectedTerm}
              onChange={(e) => setSelectedTerm(e.target.value)}
            >
              <option value="all">All Terms</option>
              {terms.map((term) => (
                <option key={term.id} value={term.id}>
                  {term.name}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Grades Table */}
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead className="bg-gray-50">
              <tr>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Subject
                </th>
                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Term
                </th>
                <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Score
                </th>
                <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Grade
                </th>
                <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Class Average
                </th>
                <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Rank
                </th>
              </tr>
            </thead>
            <tbody className="bg-white divide-y divide-gray-200">
              {filteredGrades.length > 0 ? (
                filteredGrades.map((grade) => (
                  <tr key={`${grade.subject}-${grade.term}`} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm font-medium text-gray-900">
                        {getSubjectName(grade.subject)}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <div className="text-sm text-gray-500">
                        {getTermName(grade.term)}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-center">
                      <div className="text-sm text-gray-900">
                        {grade.score}/{grade.maxScore}
                      </div>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-center">
                      <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${
                        grade.grade.startsWith('A') ? 'bg-green-100 text-green-800' :
                        grade.grade.startsWith('B') ? 'bg-blue-100 text-blue-800' :
                        grade.grade.startsWith('C') ? 'bg-yellow-100 text-yellow-800' :
                        'bg-red-100 text-red-800'
                      }`}>
                        {grade.grade}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                      {grade.average}%
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                      {grade.rank} of {grade.totalStudents}
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan="6" className="px-6 py-4 text-center text-sm text-gray-500">
                    No grades found matching the selected filters.
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

export default GradesPage;
