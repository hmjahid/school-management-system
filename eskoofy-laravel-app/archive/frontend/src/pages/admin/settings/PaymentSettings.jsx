import React, { useState } from 'react';
import { FiCreditCard, FiDollarSign, FiInfo, FiFileText } from 'react-icons/fi';

const PaymentSettings = () => {
  const [activeGateway, setActiveGateway] = useState('stripe');
  
  const [formData, setFormData] = useState({
    // General settings
    currency: 'USD',
    taxRate: 0,
    invoicePrefix: 'INV',
    invoiceLogo: null,
    invoiceTerms: 'Payment is due within 30 days. Please include the invoice number in your payment reference.',
    
    // Stripe settings
    stripePublicKey: '',
    stripeSecretKey: '',
    stripeWebhookSecret: '',
    
    // PayPal settings
    paypalClientId: '',
    paypalSecret: '',
    paypalSandbox: false,
    
    // Bank transfer settings
    bankName: '',
    accountName: '',
    accountNumber: '',
    iban: '',
    swiftCode: '',
    bankAddress: '',
  });

  const currencies = [
    { code: 'USD', name: 'US Dollar' },
    { code: 'EUR', name: 'Euro' },
    { code: 'GBP', name: 'British Pound' },
    { code: 'JPY', name: 'Japanese Yen' },
    { code: 'AUD', name: 'Australian Dollar' },
    { code: 'CAD', name: 'Canadian Dollar' },
  ];

  const paymentGateways = [
    { id: 'stripe', name: 'Stripe', icon: '💳' },
    { id: 'paypal', name: 'PayPal', icon: '🔵' },
    { id: 'bank', name: 'Bank Transfer', icon: '🏦' },
    { id: 'offline', name: 'Offline Payment', icon: '💵' },
  ];

  const handleChange = (e) => {
    const { name, value, type, checked, files } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : (type === 'file' ? files[0] : value)
    }));
  };

  const handleFileUpload = (e, field) => {
    if (e.target.files && e.target.files[0]) {
      setFormData(prev => ({
        ...prev,
        [field]: e.target.files[0]
      }));
    }
  };

  const renderGatewaySettings = () => {
    switch (activeGateway) {
      case 'stripe':
        return (
          <div className="space-y-4">
            <div className="bg-blue-50 border-l-4 border-blue-400 p-4">
              <div className="flex">
                <div className="flex-shrink-0">
                  <FiInfo className="h-5 w-5 text-blue-400" />
                </div>
                <div className="ml-3">
                  <p className="text-sm text-blue-700">
                    Stripe is a popular payment processor that supports credit cards and other payment methods.
                    You'll need to sign up for a <a href="https://stripe.com/" target="_blank" rel="noopener noreferrer" className="font-medium underline">Stripe account</a> to get your API keys.
                  </p>
                </div>
              </div>
            </div>
            
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-6">
                <label htmlFor="stripePublicKey" className="block text-sm font-medium text-gray-700">
                  Publishable Key <span className="text-red-500">*</span>
                </label>
                <div className="mt-1">
                  <input
                    type="password"
                    name="stripePublicKey"
                    id="stripePublicKey"
                    value={formData.stripePublicKey}
                    onChange={handleChange}
                    placeholder="pk_test_..."
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-6">
                <label htmlFor="stripeSecretKey" className="block text-sm font-medium text-gray-700">
                  Secret Key <span className="text-red-500">*</span>
                </label>
                <div className="mt-1">
                  <input
                    type="password"
                    name="stripeSecretKey"
                    id="stripeSecretKey"
                    value={formData.stripeSecretKey}
                    onChange={handleChange}
                    placeholder="sk_test_..."
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-6">
                <label htmlFor="stripeWebhookSecret" className="block text-sm font-medium text-gray-700">
                  Webhook Secret Key
                </label>
                <div className="mt-1">
                  <input
                    type="password"
                    name="stripeWebhookSecret"
                    id="stripeWebhookSecret"
                    value={formData.stripeWebhookSecret}
                    onChange={handleChange}
                    placeholder="whsec_..."
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
                <p className="mt-1 text-xs text-gray-500">
                  Required for webhook events like payment confirmation
                </p>
              </div>
            </div>
          </div>
        );

      case 'paypal':
        return (
          <div className="space-y-4">
            <div className="bg-blue-50 border-l-4 border-blue-400 p-4">
              <div className="flex">
                <div className="flex-shrink-0">
                  <FiInfo className="h-5 w-5 text-blue-400" />
                </div>
                <div className="ml-3">
                  <p className="text-sm text-blue-700">
                    PayPal allows your customers to pay using their PayPal accounts or credit cards.
                    You'll need to create a <a href="https://developer.paypal.com/" target="_blank" rel="noopener noreferrer" className="font-medium underline">PayPal Developer account</a> to get your API credentials.
                  </p>
                </div>
              </div>
            </div>
            
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-6">
                <label htmlFor="paypalClientId" className="block text-sm font-medium text-gray-700">
                  Client ID <span className="text-red-500">*</span>
                </label>
                <div className="mt-1">
                  <input
                    type="password"
                    name="paypalClientId"
                    id="paypalClientId"
                    value={formData.paypalClientId}
                    onChange={handleChange}
                    placeholder="AeA1..."
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-6">
                <label htmlFor="paypalSecret" className="block text-sm font-medium text-gray-700">
                  Secret Key <span className="text-red-500">*</span>
                </label>
                <div className="mt-1">
                  <input
                    type="password"
                    name="paypalSecret"
                    id="paypalSecret"
                    value={formData.paypalSecret}
                    onChange={handleChange}
                    placeholder="EC..."
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-6">
                <div className="flex items-start">
                  <div className="flex items-center h-5">
                    <input
                      id="paypalSandbox"
                      name="paypalSandbox"
                      type="checkbox"
                      checked={formData.paypalSandbox}
                      onChange={handleChange}
                      className="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                    />
                  </div>
                  <div className="ml-3 text-sm">
                    <label htmlFor="paypalSandbox" className="font-medium text-gray-700">
                      Enable Sandbox Mode
                    </label>
                    <p className="text-gray-500">Use PayPal sandbox for testing payments</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );

      case 'bank':
        return (
          <div className="space-y-4">
            <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
              <div className="flex">
                <div className="flex-shrink-0">
                  <FiInfo className="h-5 w-5 text-yellow-400" />
                </div>
                <div className="ml-3">
                  <p className="text-sm text-yellow-700">
                    When using bank transfer, payments will be marked as pending until manually verified.
                    You'll need to provide your bank account details to your customers.
                  </p>
                </div>
              </div>
            </div>
            
            <div className="grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
              <div className="sm:col-span-6">
                <label htmlFor="bankName" className="block text-sm font-medium text-gray-700">
                  Bank Name
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    name="bankName"
                    id="bankName"
                    value={formData.bankName}
                    onChange={handleChange}
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-6">
                <label htmlFor="accountName" className="block text-sm font-medium text-gray-700">
                  Account Name
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    name="accountName"
                    id="accountName"
                    value={formData.accountName}
                    onChange={handleChange}
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-6">
                <label htmlFor="accountNumber" className="block text-sm font-medium text-gray-700">
                  Account Number
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    name="accountNumber"
                    id="accountNumber"
                    value={formData.accountNumber}
                    onChange={handleChange}
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="iban" className="block text-sm font-medium text-gray-700">
                  IBAN
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    name="iban"
                    id="iban"
                    value={formData.iban}
                    onChange={handleChange}
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-3">
                <label htmlFor="swiftCode" className="block text-sm font-medium text-gray-700">
                  SWIFT/BIC Code
                </label>
                <div className="mt-1">
                  <input
                    type="text"
                    name="swiftCode"
                    id="swiftCode"
                    value={formData.swiftCode}
                    onChange={handleChange}
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>

              <div className="sm:col-span-6">
                <label htmlFor="bankAddress" className="block text-sm font-medium text-gray-700">
                  Bank Address
                </label>
                <div className="mt-1">
                  <textarea
                    name="bankAddress"
                    id="bankAddress"
                    rows={3}
                    value={formData.bankAddress}
                    onChange={handleChange}
                    className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  />
                </div>
              </div>
            </div>
          </div>
        );

      case 'offline':
        return (
          <div className="bg-yellow-50 border-l-4 border-yellow-400 p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <FiInfo className="h-5 w-5 text-yellow-400" />
              </div>
              <div className="ml-3">
                <p className="text-sm text-yellow-700">
                  With offline payments, you'll need to collect payments manually. 
                  This could be cash, check, or any other offline payment method.
                </p>
              </div>
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  return (
    <div className="space-y-6">
      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">Payment Gateways</h3>
          <p className="mt-1 text-sm text-gray-500">
            Configure the payment methods you want to accept.
          </p>
          
          <div className="mt-6">
            <div className="sm:hidden">
              <label htmlFor="current-tab" className="sr-only">
                Select a payment gateway
              </label>
              <select
                id="current-tab"
                name="current-tab"
                value={activeGateway}
                onChange={(e) => setActiveGateway(e.target.value)}
                className="block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
              >
                {paymentGateways.map((gateway) => (
                  <option key={gateway.id} value={gateway.id}>
                    {gateway.name}
                  </option>
                ))}
              </select>
            </div>
            <div className="hidden sm:block">
              <div className="border-b border-gray-200">
                <nav className="-mb-px flex space-x-8">
                  {paymentGateways.map((gateway) => (
                    <button
                      key={gateway.id}
                      onClick={() => setActiveGateway(gateway.id)}
                      className={`${activeGateway === gateway.id
                        ? 'border-indigo-500 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                      } whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm`}
                    >
                      <span className="mr-2">{gateway.icon}</span>
                      {gateway.name}
                    </button>
                  ))}
                </nav>
              </div>
            </div>
          </div>
          
          <div className="mt-6">
            {renderGatewaySettings()}
          </div>
        </div>
      </div>

      <div className="bg-white shadow overflow-hidden sm:rounded-lg">
        <div className="px-4 py-5 sm:p-6">
          <h3 className="text-lg font-medium text-gray-900">General Payment Settings</h3>
          
          <div className="mt-6 grid grid-cols-1 gap-y-6 gap-x-4 sm:grid-cols-6">
            <div className="sm:col-span-3">
              <label htmlFor="currency" className="block text-sm font-medium text-gray-700">
                Default Currency
              </label>
              <div className="mt-1">
                <select
                  id="currency"
                  name="currency"
                  value={formData.currency}
                  onChange={handleChange}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                >
                  {currencies.map((currency) => (
                    <option key={currency.code} value={currency.code}>
                      {currency.name} ({currency.code})
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="sm:col-span-3">
              <label htmlFor="taxRate" className="block text-sm font-medium text-gray-700">
                Tax Rate (%)
              </label>
              <div className="mt-1 relative rounded-md shadow-sm">
                <input
                  type="number"
                  name="taxRate"
                  id="taxRate"
                  min="0"
                  max="100"
                  step="0.01"
                  value={formData.taxRate}
                  onChange={handleChange}
                  className="focus:ring-indigo-500 focus:border-indigo-500 block w-full pr-10 sm:text-sm border-gray-300 rounded-md"
                />
                <div className="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                  <span className="text-gray-500 sm:text-sm">%</span>
                </div>
              </div>
            </div>

            <div className="sm:col-span-6 border-t border-gray-200 pt-6">
              <h4 className="text-md font-medium text-gray-900">Invoice Settings</h4>
            </div>

            <div className="sm:col-span-3">
              <label htmlFor="invoicePrefix" className="block text-sm font-medium text-gray-700">
                Invoice Prefix
              </label>
              <div className="mt-1">
                <input
                  type="text"
                  name="invoicePrefix"
                  id="invoicePrefix"
                  value={formData.invoicePrefix}
                  onChange={handleChange}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                />
              </div>
              <p className="mt-1 text-xs text-gray-500">
                Prefix for invoice numbers (e.g., INV-2023-)
              </p>
            </div>

            <div className="sm:col-span-6">
              <label className="block text-sm font-medium text-gray-700">
                Invoice Logo
              </label>
              <div className="mt-1 flex items-center">
                <span className="h-12 w-32 overflow-hidden bg-gray-100 border border-dashed border-gray-300 rounded flex items-center justify-center">
                  {formData.invoiceLogo ? (
                    <img
                      src={URL.createObjectURL(formData.invoiceLogo)}
                      alt="Invoice logo preview"
                      className="h-full w-full object-contain"
                    />
                  ) : (
                    <div className="text-gray-400">
                      <FiFileText className="h-6 w-6 mx-auto" />
                      <span className="text-xs block mt-1">No logo</span>
                    </div>
                  )}
                </span>
                <label
                  htmlFor="invoice-logo-upload"
                  className="ml-5 bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer"
                >
                  <span>Change</span>
                  <input
                    id="invoice-logo-upload"
                    type="file"
                    className="sr-only"
                    accept="image/*"
                    onChange={(e) => handleFileUpload(e, 'invoiceLogo')}
                  />
                </label>
              </div>
              <p className="mt-1 text-xs text-gray-500">
                Recommended size: 200px x 50px (transparent PNG for best results)
              </p>
            </div>

            <div className="sm:col-span-6">
              <label htmlFor="invoiceTerms" className="block text-sm font-medium text-gray-700">
                Terms & Conditions
              </label>
              <div className="mt-1">
                <textarea
                  id="invoiceTerms"
                  name="invoiceTerms"
                  rows={3}
                  className="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md"
                  value={formData.invoiceTerms}
                  onChange={handleChange}
                />
              </div>
              <p className="mt-1 text-xs text-gray-500">
                These terms will appear at the bottom of each invoice
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default PaymentSettings;
