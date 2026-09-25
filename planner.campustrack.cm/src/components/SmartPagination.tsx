import React from 'react';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationLink,
  PaginationPrevious,
  PaginationNext,
  PaginationEllipsis,
} from '@/src/components/ui/pagination';

type SmartPaginationProps = {
  currentPage: number;
  totalPages: number;
  onPageChange?: (page: number) => void;
  /** N'affiche rien s'il n'y a qu'une page */
  hideOnSinglePage?: boolean;
};

/**
 * Pagination avec ellipsis (> 7 pages), autrefois dupliquée dans chaque Manager.
 */
export const SmartPagination: React.FC<SmartPaginationProps> = ({
  currentPage,
  totalPages,
  onPageChange,
  hideOnSinglePage = true,
}) => {
  if (totalPages <= 1 && hideOnSinglePage) return null;

  const pages =
    totalPages <= 7
      ? Array.from({ length: totalPages }, (_, i) => i + 1)
      : Array.from({ length: Math.min(7, totalPages - 2) }, (_, i) => {
          if (currentPage <= 4) return i + 2;
          if (currentPage >= totalPages - 3) return totalPages - 7 + i;
          return currentPage - 3 + i;
        })
          .filter((page) => page > 1 && page < totalPages)
          .map((page) => page);

  return (
    <div className="p-4 border-t border-gray-200 col-span-full">
      <Pagination>
        <PaginationContent className="ml-auto">
          <PaginationItem>
            <PaginationPrevious
              onClick={() => onPageChange?.(currentPage - 1)}
              className={currentPage === 1 ? 'pointer-events-none opacity-50' : 'cursor-pointer'}
            />
          </PaginationItem>

          {totalPages > 7 && (
            <>
              <PaginationItem>
                <PaginationLink
                  onClick={() => onPageChange?.(1)}
                  isActive={currentPage === 1}
                  className="cursor-pointer"
                >
                  1
                </PaginationLink>
              </PaginationItem>
              {currentPage > 3 && <PaginationEllipsis />}
            </>
          )}

          {pages.map((page) => (
            <PaginationItem key={page}>
              <PaginationLink
                onClick={() => onPageChange?.(page)}
                isActive={currentPage === page}
                className="cursor-pointer"
              >
                {page}
              </PaginationLink>
            </PaginationItem>
          ))}

          {totalPages > 7 && (
            <>
              {currentPage < totalPages - 2 && <PaginationEllipsis />}
              <PaginationItem>
                <PaginationLink
                  onClick={() => onPageChange?.(totalPages)}
                  isActive={currentPage === totalPages}
                  className="cursor-pointer"
                >
                  {totalPages}
                </PaginationLink>
              </PaginationItem>
            </>
          )}

          <PaginationItem>
            <PaginationNext
              onClick={() => onPageChange?.(currentPage + 1)}
              className={
                currentPage === totalPages ? 'pointer-events-none opacity-50' : 'cursor-pointer'
              }
            />
          </PaginationItem>
        </PaginationContent>
      </Pagination>
    </div>
  );
};
