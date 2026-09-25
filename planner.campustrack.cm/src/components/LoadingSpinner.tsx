import React from 'react';

type LoadingSpinnerProps = {
  message?: string;
  fullScreen?: boolean;
};

export const LoadingSpinner: React.FC<LoadingSpinnerProps> = ({
  message = 'Loading...',
  fullScreen = true,
}) => {
  if (fullScreen) {
    return (
      <div className="flex items-center justify-center min-h-screen bg-linear-to-br from-indigo-100 to-purple-100">
        <div className="text-center">
          <div className="animate-spin rounded-full size-12 border-b-2 border-secondary mx-auto mb-4"></div>
          <p className="text-gray-600">{message}</p>
        </div>
      </div>
    );
  }

  return (
    <div className="flex items-center justify-center p-8">
      <div className="text-center">
        <div className="animate-spin rounded-full size-8 border-b-2 border-secondary mx-auto mb-2"></div>
        <p className="text-gray-600 text-sm">{message}</p>
      </div>
    </div>
  );
};
