import React from 'react';
import { Routes, Route, Navigate } from 'react-router-dom';
import CMSLayout from './CMSLayout';
import PagesList from './pages/PagesList';
import PageEditor from './pages/PageEditor';
import MediaManager from './media/MediaManager';
import MenuBuilder from './menus/MenuBuilder';
import HeaderFooterEditor from './header/HeaderFooterEditor';

const CMS = () => {
  return (
    <Routes>
      <Route path="/" element={<CMSLayout />}>
        <Route index element={<Navigate to="pages" replace />} />
        <Route path="pages">
          <Route index element={<PagesList />} />
          <Route path="new" element={<PageEditor />} />
          <Route path=":id/edit" element={<PageEditor />} />
        </Route>
        <Route path="media" element={<MediaManager />} />
        <Route path="menus" element={<MenuBuilder />} />
        <Route path="header-footer" element={<HeaderFooterEditor />} />
        {/* Add other CMS routes here */}
      </Route>
    </Routes>
  );
};

export default CMS;
