import React, { useState, useEffect } from 'react';
import axiosInstance from '../utils/axios';
import debounce from 'lodash/debounce';
import { motion, AnimatePresence } from 'framer-motion';
import { Dialog } from '@headlessui/react';
import { XMarkIcon } from '@heroicons/react/24/outline';

const ITEMS_PER_PAGE = 100;

const Gallery = () => {
    const [images, setImages] = useState([]);
    const [pagination, setPagination] = useState({
        currentPage: 1,
        perPage: ITEMS_PER_PAGE,
        total: 0
    });
    const [isLoading, setIsLoading] = useState(true);
    const [isSearching, setIsSearching] = useState(false);
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedImage, setSelectedImage] = useState(null);
    const [photographers, setPhotographers] = useState([]);
    const [selectedPhotographers, setSelectedPhotographers] = useState([]);
    const [availablePhotographers, setAvailablePhotographers] = useState([]);
    const [dateRange, setDateRange] = useState({ startDate: '', endDate: '' });

    const searchImages = async (query = '', page = 1) => {
        setIsLoading(true);
        try {
            const endpoint = '/api/images/search';
            const params = {
                q: query,
                per_page: ITEMS_PER_PAGE,
                page,
                photographers: selectedPhotographers.length > 0 ? selectedPhotographers.join(',') : undefined,
                start_date: dateRange.startDate || undefined,
                end_date: dateRange.endDate || undefined
            };
            
            console.log('Fetching images:', { endpoint, params });
            const response = await axiosInstance.get(endpoint, { params });
            
            console.log('Results:', response.data);
            setImages(response.data.data || []);
            setPagination({
                currentPage: parseInt(response.data.meta.current_page) || 1,
                perPage: parseInt(response.data.meta.per_page) || ITEMS_PER_PAGE,
                total: parseInt(response.data.meta.total) || 0
            });
            setAvailablePhotographers(response.data.aggregations.photographers || []);
            setIsSearching(!!query || selectedPhotographers.length > 0 || dateRange.startDate || dateRange.endDate);
        } catch (error) {
            console.error('Error fetching images:', error);
            setImages([]);
            setPagination({
                currentPage: 1,
                perPage: ITEMS_PER_PAGE,
                total: 0
            });
            setAvailablePhotographers([]);
        } finally {
            setIsLoading(false);
        }
    };

    const debouncedSearch = debounce(searchImages, 300);

    const handlePageChange = (newPage) => {
        if (newPage >= 1 && newPage <= Math.ceil(pagination.total / pagination.perPage)) {
            searchImages(searchTerm, newPage);
        }
    };

    const handlePhotographerClick = (photographer) => {
        setSelectedPhotographers(prevPhotographers => {
            if (prevPhotographers.includes(photographer)) {
                return prevPhotographers.filter(p => p !== photographer);
            } else {
                return [...prevPhotographers, photographer];
            }
        });
    };

    const handleDateChange = (field, value) => {
        setDateRange(prev => ({ ...prev, [field]: value }));
    };

    const clearDateFilter = () => {
        setDateRange({ startDate: '', endDate: '' });
    };

    const isPhotographerAvailable = (photographer) => {
        return availablePhotographers.some(availablePhotographer => availablePhotographer.key === photographer);
    };

    useEffect(() => {
        if (searchTerm.trim() || selectedPhotographers.length > 0 || dateRange.startDate || dateRange.endDate) {
            debouncedSearch(searchTerm);
        } else {
            debouncedSearch('');
        }
        return () => debouncedSearch.cancel();
    }, [searchTerm, selectedPhotographers, dateRange]);

    useEffect(() => {
        const fetchPhotographers = async () => {
            try {
                const response = await axiosInstance.get('/api/images/photographers');
                const sortedPhotographers = response.data.sort((a, b) => b.doc_count - a.doc_count);
                setPhotographers(sortedPhotographers);
            } catch (error) {
                console.error('Error fetching photographers:', error);
            }
        };

        fetchPhotographers();
    }, []);

    return (
        <div className="container mx-auto px-4 py-8">
            <div className="mb-6 relative">
                <div>
                    <input
                        type="text"
                        className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Search images by title, description, or photographers..."
                        value={searchTerm}
                        onChange={(e) => setSearchTerm(e.target.value)}
                    />
                    {isLoading && (
                        <div className="absolute right-3 top-1/2 transform -translate-y-1/2">
                            <div className="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-500"></div>
                        </div>
                    )}
                </div>
            </div>

            <div className="mb-8">
                <h2 className="text-2xl font-bold mb-4">Filter by Date</h2>
                <div className="flex gap-4 items-center mb-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input
                            type="date"
                            value={dateRange.startDate}
                            onChange={(e) => handleDateChange('startDate', e.target.value)}
                            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input
                            type="date"
                            value={dateRange.endDate}
                            onChange={(e) => handleDateChange('endDate', e.target.value)}
                            className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>
                    {(dateRange.startDate || dateRange.endDate) && (
                        <button
                            onClick={clearDateFilter}
                            className="mt-6 text-sm text-blue-500 hover:text-blue-700"
                        >
                            Clear date filter
                        </button>
                    )}
                </div>
            </div>

            <div className="mb-8">
                <h2 className="text-2xl font-bold mb-4">Filter by Photographers</h2>
                <div className="flex flex-wrap gap-2">
                    {photographers.map((photographer) => (
                        <button
                            key={photographer.key}
                            onClick={() => handlePhotographerClick(photographer.key)}
                            disabled={!isPhotographerAvailable(photographer.key) && !selectedPhotographers.includes(photographer.key)}
                            className={`px-4 py-2 rounded-full text-sm font-medium transition-colors
                                ${selectedPhotographers.includes(photographer.key)
                                    ? 'bg-blue-500 text-white'
                                    : !isPhotographerAvailable(photographer.key)
                                    ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                }`}
                        >
                            {photographer.key} ({isPhotographerAvailable(photographer.key) ? availablePhotographers.find(p => p.key === photographer.key).doc_count : 0})
                        </button>
                    ))}
                </div>
                {selectedPhotographers.length > 0 && (
                    <button
                        onClick={() => setSelectedPhotographers([])}
                        className="mt-4 text-sm text-blue-500 hover:text-blue-700"
                    >
                        Clear all photographers
                    </button>
                )}
            </div>

            {images.length === 0 ? (
                <div className="text-center py-12">
                    <p className="text-gray-500">
                        {isSearching ? 'No images found matching your search.' : 'No images found.'}
                    </p>
                </div>
            ) : (
                <>
                    <div className="mb-4 text-gray-600">
                        {isSearching ? 'Found' : 'Showing'} {pagination.total} {pagination.total === 1 ? 'image' : 'images'}
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <AnimatePresence>
                            {images.map((image) => (
                                <div key={image.id} className="relative group">
                                    <img
                                        src={image.edited_image}
                                        alt={image.title}
                                        className="w-full h-48 object-cover rounded-lg shadow-md"
                                        onError={(e) => {
                                            e.target.onerror = null; // Prevent infinite loop
                                            e.target.src = 'https://via.placeholder.com/300x200?text=No+Image+Available';
                                        }}
                                    />
                                    <div className="mt-2 text-sm text-gray-600">
                                        <p className="line-clamp-2"><strong>Title:</strong> {image.title || image.description || image.search_text}</p>
                                        <p><strong>Photographers:</strong> {Array.isArray(image.photographers) ? image.photographers.join(', ') : image.photographers || 'N/A'}</p>
                                        <p><strong>Date:</strong> {new Date(image.date).toLocaleDateString()}</p>
                                        <p><strong>Dimensions:</strong> {image.dimensions.width}x{image.dimensions.height}</p>
                                        <p><strong>Database:</strong> {image.database}</p>
                                        {image.inner_hits?.similar_docs?.hits?.length > 0 && (
                                            <div className="mt-2 pt-2 border-t border-gray-200">
                                                <p className="text-xs text-gray-500">
                                                    <strong>Similar documents:</strong> {image.inner_hits.similar_docs.hits.length}
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </AnimatePresence>
                    </div>

                    {pagination.total > pagination.perPage && (
                        <div className="mt-8 flex justify-center space-x-4">
                            <button
                                onClick={() => handlePageChange(pagination.currentPage - 1)}
                                disabled={pagination.currentPage === 1}
                                className="px-4 py-2 border rounded-lg disabled:opacity-50"
                            >
                                Previous
                            </button>
                            <span className="px-4 py-2">
                                Page {pagination.currentPage} of {Math.ceil(pagination.total / pagination.perPage)}
                            </span>
                            <button
                                onClick={() => handlePageChange(pagination.currentPage + 1)}
                                disabled={pagination.currentPage >= Math.ceil(pagination.total / pagination.perPage)}
                                className="px-4 py-2 border rounded-lg disabled:opacity-50"
                            >
                                Next
                            </button>
                        </div>
                    )}
                </>
            )}
        </div>
    );
};

export default Gallery; 