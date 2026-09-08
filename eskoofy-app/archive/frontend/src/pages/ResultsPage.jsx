import { useState, useEffect } from 'react';
import resultService from '../services/resultService';
import LoadingSpinner from '../components/common/LoadingSpinner';

const ResultsPage = () => {
  const [classes, setClasses] = useState([]);
  const [sessions, setSessions] = useState([]);
  const [form, setForm] = useState({ class_id: '', academic_session_id: '', roll: '' });
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [filtersLoading, setFiltersLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchFilters = async () => {
      setFiltersLoading(true);
      const res = await resultService.getFilters();
      if (res.success) {
        setClasses(res.data.classes || []);
        setSessions(res.data.sessions || []);
      } else {
        setError(res.error);
      }
      setFiltersLoading(false);
    };
    fetchFilters();
  }, []);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    setData(null);
    const res = await resultService.lookupResults({
      class_id: form.class_id,
      academic_session_id: form.academic_session_id,
      roll: form.roll,
    });
    if (res.success) {
      setData(res.data);
    } else {
      setError(res.error);
    }
    setLoading(false);
  };

  return (
    <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
      <h1 className="mb-2 text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
        Results
      </h1>
      <p className="mb-8 max-w-3xl text-lg text-gray-600">
        Search published exam results by class, year, and roll number.
      </p>

      <form onSubmit={handleSubmit} className="mb-8 grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm sm:grid-cols-4">
        <div>
          <label className="mb-1 block text-xs font-semibold text-slate-600">Class</label>
          <select name="class_id" required value={form.class_id} onChange={handleChange} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" disabled={filtersLoading}>
            <option value="">—</option>
            {classes.map((c) => (
              <option key={c.id} value={c.id}>{c.name}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1 block text-xs font-semibold text-slate-600">Year</label>
          <select name="academic_session_id" required value={form.academic_session_id} onChange={handleChange} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" disabled={filtersLoading}>
            <option value="">—</option>
            {sessions.map((s) => (
              <option key={s.id} value={s.id}>{s.name}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1 block text-xs font-semibold text-slate-600">Roll number</label>
          <input type="text" name="roll" required value={form.roll} onChange={handleChange} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" />
        </div>
        <div className="flex items-end">
          <button type="submit" className="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50" disabled={loading || filtersLoading}>
            {loading ? 'Searching...' : 'Search'}
          </button>
        </div>
      </form>

      {loading && <LoadingSpinner />}

      {error && (
        <div className="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
          {error}
        </div>
      )}

      {data && (
        <div className="space-y-6">
          <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
            <h3 className="text-lg font-semibold text-slate-800">
              {data.student?.name || 'Student'}
              <span className="ml-2 text-sm font-normal text-slate-500">
                (Roll: {data.student?.roll_no || data.student?.roll_number || '—'})
              </span>
            </h3>
            {data.student?.class && (
              <p className="text-sm text-slate-500">Class: {data.student.class}</p>
            )}
          </div>

          {data.exams?.length > 0 ? data.exams.map((exam) => (
            <div key={exam.id} className="rounded-lg border border-slate-200 bg-white shadow-sm">
              <div className="border-b border-slate-100 px-4 py-3">
                <h4 className="text-base font-semibold text-slate-800">{exam.name}</h4>
                {exam.total_marks && (
                  <p className="text-xs text-slate-500">Total Marks: {exam.total_marks}</p>
                )}
              </div>
              {exam.results?.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="min-w-full divide-y divide-slate-200 text-sm">
                    <thead className="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-600">
                      <tr>
                        <th className="px-4 py-3">Subject</th>
                        <th className="px-4 py-3 text-right">Marks</th>
                        <th className="px-4 py-3">Grade</th>
                        <th className="px-4 py-3">Remarks</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {exam.results.map((r) => (
                        <tr key={r.id}>
                          <td className="px-4 py-2 font-medium text-slate-900">{r.subject || '—'}</td>
                          <td className="px-4 py-2 text-right font-mono">{r.obtained_marks ?? '—'} / {exam.total_marks ?? '—'}</td>
                          <td className="px-4 py-2">{r.grade || '—'}</td>
                          <td className="px-4 py-2 text-slate-600">{r.remarks || ''}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <p className="p-4 text-center text-sm text-slate-500">No results available for this exam.</p>
              )}
            </div>
          )) : (
            <div className="rounded-lg border border-slate-200 bg-white p-6 text-center text-sm text-slate-600 shadow-sm">
              No results found for the given roll number.
            </div>
          )}
        </div>
      )}
    </div>
  );
};

export default ResultsPage;
