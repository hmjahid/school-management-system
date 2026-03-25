import React, { useState } from 'react';
import { FiBell, FiMail, FiPhone, FiAlertCircle, FiCheckCircle, FiClock, FiUser, FiDollarSign, FiBookOpen } from 'react-icons/fi';

const NotificationSettings = () => {
  const [notificationSettings, setNotificationSettings] = useState({
    // Notification methods
    emailNotifications: true,
    smsNotifications: false,
    pushNotifications: true,
    
    // Email notifications
    emailNewUser: true,
    emailNewPayment: true,
    emailPaymentReminder: true,
    emailAttendanceUpdate: true,
    emailAssignmentPosted: true,
    emailExamSchedule: true,
    emailResultsPublished: true,
    emailNewsAndUpdates: true,
    
    // SMS notifications
    smsPaymentReminder: false,
    smsAttendanceAlert: false,
    smsExamReminder: false,
    
    // Push notifications
    pushNewMessage: true,
    pushAssignmentGraded: true,
    pushAnnouncement: true,
    
    // Notification preferences
    notificationEmail: '',
    notificationPhone: '',
    notifyBeforeDays: 1,
  });

  const handleToggle = (field) => {
    setNotificationSettings(prev => ({
      ...prev,
      [field]: !prev[field]
    }));
  };

  const handleChange = (e) => {
    const { name, value, type, checked } = e.target;
    setNotificationSettings(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const NotificationToggle = ({ label, description, checked, onChange, icon: Icon }) => (
    <div className="flex items-start py-4">
      <div className="flex items-center h-5">
        <input
          type="checkbox"
          checked={checked}
          onChange={onChange}
          className="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
        />
      </div>
      <div className="ml-3 text-sm">
        <div className="flex items-center">
          {Icon && <Icon className="mr-2 h-4 w-4 text-gray-500" />}
          <label className="font-medium text-gray-700">{label}</label>
        </div>
        {description && <p className="text-gray-500">{description}</p>}
      </div>
    </div>
  );

  const NotificationSection = ({ title, children }) => (
    <div className="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
      <div className="px-4 py-5 sm:px-6 border-b border-gray-200">
        <h3 className="text-lg leading-6 font-medium text-gray-900">{title}</h3>
      </div>
      <div className="px-4 py-5 sm:p-6">
        <div className="space-y-4">
          {children}
        </div>
      </div>
    </div>
  );

  return (
    <div className="space-y-6">
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">Notification Settings</h3>
          <p className="mt-1 text-sm text-gray-500">
            Manage how you receive notifications from the system.
          </p>
        </div>
      </div>

      <NotificationSection title="Notification Methods">
        <NotificationToggle
          label="Email Notifications"
          description="Receive notifications via email"
          checked={notificationSettings.emailNotifications}
          onChange={() => handleToggle('emailNotifications')}
          icon={FiMail}
        />
        <NotificationToggle
          label="SMS Notifications"
          description="Receive notifications via text message (standard rates may apply)"
          checked={notificationSettings.smsNotifications}
          onChange={() => handleToggle('smsNotifications')}
          icon={FiPhone}
        />
        <NotificationToggle
          label="Push Notifications"
          description="Receive notifications in your browser"
          checked={notificationSettings.pushNotifications}
          onChange={() => handleToggle('pushNotifications')}
          icon={FiBell}
        />
      </NotificationSection>

      {notificationSettings.emailNotifications && (
        <NotificationSection 
          title="Email Notifications"
          icon={FiMail}
        >
          <NotificationToggle
            label="New User Registration"
            description="When a new user registers an account"
            checked={notificationSettings.emailNewUser}
            onChange={() => handleToggle('emailNewUser')}
          />
          <NotificationToggle
            label="New Payment Received"
            description="When a payment is successfully processed"
            checked={notificationSettings.emailNewPayment}
            onChange={() => handleToggle('emailNewPayment')}
          />
          <NotificationToggle
            label="Payment Reminders"
            description="Upcoming and overdue payment reminders"
            checked={notificationSettings.emailPaymentReminder}
            onChange={() => handleToggle('emailPaymentReminder')}
          />
          <NotificationToggle
            label="Attendance Updates"
            description="When attendance is marked or updated"
            checked={notificationSettings.emailAttendanceUpdate}
            onChange={() => handleToggle('emailAttendanceUpdate')}
          />
          <NotificationToggle
            label="New Assignment Posted"
            description="When a teacher posts a new assignment"
            checked={notificationSettings.emailAssignmentPosted}
            onChange={() => handleToggle('emailAssignmentPosted')}
          />
          <NotificationToggle
            label="Exam Schedule Updates"
            description="When exam schedules are published or updated"
            checked={notificationSettings.emailExamSchedule}
            onChange={() => handleToggle('emailExamSchedule')}
          />
          <NotificationToggle
            label="Exam Results Published"
            description="When exam results are published"
            checked={notificationSettings.emailResultsPublished}
            onChange={() => handleToggle('emailResultsPublished')}
          />
          <NotificationToggle
            label="News and Updates"
            description="Important announcements and system updates"
            checked={notificationSettings.emailNewsAndUpdates}
            onChange={() => handleToggle('emailNewsAndUpdates')}
          />
        </NotificationSection>
      )}

      {notificationSettings.smsNotifications && (
        <NotificationSection 
          title="SMS Notifications"
          icon={FiPhone}
        >
          <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
            <div className="flex">
              <FiAlertCircle className="h-5 w-5 text-yellow-400" />
              <div className="ml-3">
                <p className="text-sm text-yellow-700">
                  Standard messaging rates may apply for SMS notifications.
                </p>
              </div>
            </div>
          </div>
          
          <NotificationToggle
            label="Payment Reminders"
            description="Get SMS reminders for upcoming and overdue payments"
            checked={notificationSettings.smsPaymentReminder}
            onChange={() => handleToggle('smsPaymentReminder')}
          />
          <NotificationToggle
            label="Attendance Alerts"
            description="Get alerts when your child's attendance is marked"
            checked={notificationSettings.smsAttendanceAlert}
            onChange={() => handleToggle('smsAttendanceAlert')}
          />
          <NotificationToggle
            label="Exam Reminders"
            description="Reminders for upcoming exams"
            checked={notificationSettings.smsExamReminder}
            onChange={() => handleToggle('smsExamReminder')}
          />
        </NotificationSection>
      )}

      {notificationSettings.pushNotifications && (
        <NotificationSection 
          title="Push Notifications"
          icon={FiBell}
        >
          <NotificationToggle
            label="New Messages"
            description="When you receive a new message"
            checked={notificationSettings.pushNewMessage}
            onChange={() => handleToggle('pushNewMessage')}
          />
          <NotificationToggle
            label="Assignment Graded"
            description="When an assignment is graded"
            checked={notificationSettings.pushAssignmentGraded}
            onChange={() => handleToggle('pushAssignmentGraded')}
          />
          <NotificationToggle
            label="Announcements"
            description="Important announcements from school"
            checked={notificationSettings.pushAnnouncement}
            onChange={() => handleToggle('pushAnnouncement')}
          />
        </NotificationSection>
      )}

      <NotificationSection title="Notification Preferences">
        <div className="space-y-6">
          <div>
            <label htmlFor="notificationEmail" className="block text-sm font-medium text-gray-700">
              Notification Email Address
            </label>
            <div className="mt-1">
              <input
                type="email"
                name="notificationEmail"
                id="notificationEmail"
                value={notificationSettings.notificationEmail}
                onChange={handleChange}
                className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                placeholder="your@email.com"
              />
            </div>
            <p className="mt-2 text-sm text-gray-500">
              This email will receive all system notifications
            </p>
          </div>

          {notificationSettings.smsNotifications && (
            <div>
              <label htmlFor="notificationPhone" className="block text-sm font-medium text-gray-700">
                Phone Number for SMS Alerts
              </label>
              <div className="mt-1">
                <input
                  type="tel"
                  name="notificationPhone"
                  id="notificationPhone"
                  value={notificationSettings.notificationPhone}
                  onChange={handleChange}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  placeholder="+1 (555) 123-4567"
                />
              </div>
              <p className="mt-2 text-sm text-gray-500">
                Include country code. Standard messaging rates may apply.
              </p>
            </div>
          )}

          <div>
            <label htmlFor="notifyBeforeDays" className="block text-sm font-medium text-gray-700">
              Send Reminders Before (Days)
            </label>
            <div className="mt-1">
              <select
                id="notifyBeforeDays"
                name="notifyBeforeDays"
                value={notificationSettings.notifyBeforeDays}
                onChange={handleChange}
                className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
              >
                <option value="1">1 day before</option>
                <option value="2">2 days before</option>
                <option value="3">3 days before</option>
                <option value="7">1 week before</option>
                <option value="14">2 weeks before</option>
              </select>
            </div>
            <p className="mt-2 text-sm text-gray-500">
              How many days in advance to send reminders for upcoming events
            </p>
          </div>
        </div>
      </NotificationSection>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">Notification History</h3>
          <div className="mt-4 bg-gray-50 p-4 rounded-md">
            <div className="flow-root">
              <ul className="-mb-8">
                {[
                  { id: 1, type: 'assignment', title: 'New Assignment Posted', description: 'Math Homework #5 has been assigned', time: '2 hours ago', icon: FiBookOpen },
                  { id: 2, type: 'payment', title: 'Payment Received', description: 'Payment of $250.00 has been received', time: '1 day ago', icon: FiDollarSign },
                  { id: 3, type: 'attendance', title: 'Attendance Marked', description: 'Attendance marked for 04/15/2023', time: '2 days ago', icon: FiUser },
                ].map((item, itemIdx) => (
                  <li key={item.id}>
                    <div className="relative pb-8">
                      {itemIdx !== 2 ? (
                        <span className="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200" aria-hidden="true" />
                      ) : null}
                      <div className="relative flex space-x-3">
                        <div>
                          <span className="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center ring-8 ring-white">
                            <item.icon className="h-5 w-5 text-indigo-600" />
                          </span>
                        </div>
                        <div className="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                          <div>
                            <p className="text-sm text-gray-800">
                              {item.title}
                              <span className="font-medium text-gray-900"> {item.description}</span>
                            </p>
                          </div>
                          <div className="text-right text-sm whitespace-nowrap text-gray-500">
                            <time dateTime="2023-04-15T14:00">{item.time}</time>
                          </div>
                        </div>
                      </div>
                    </div>
                  </li>
                ))}
              </ul>
            </div>
            <div className="mt-6">
              <a href="#" className="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                View all notifications<span aria-hidden="true"> &rarr;</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default NotificationSettings;
