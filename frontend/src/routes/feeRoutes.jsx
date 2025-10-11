import { lazy } from 'react';

// Lazy load components for better performance
const FeeList = lazy(() => import('../pages/fees/FeeList'));
const FeeForm = lazy(() => import('../pages/fees/FeeForm'));
const FeeDetail = lazy(() => import('../pages/fees/FeeDetailPage'));

const feeRoutes = [
  {
    path: 'fees',
    children: [
      {
        index: true,
        element: <FeeList />,
      },
      {
        path: 'new',
        element: <FeeForm />,
      },
      {
        path: ':id',
        element: <FeeDetail />,
      },
      {
        path: ':id/edit',
        element: <FeeForm isEdit={true} />,
      },
    ],
  },
];

export default feeRoutes;
