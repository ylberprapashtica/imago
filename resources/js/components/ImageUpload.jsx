import React, { useState, useCallback } from 'react';
import { useDropzone } from 'react-dropzone';
import axios from '../utils/axios';

export default function ImageUpload() {
    const [files, setFiles] = useState([]);
    const [uploading, setUploading] = useState(false);
    const [uploadProgress, setUploadProgress] = useState({});

    const onDrop = useCallback((acceptedFiles) => {
        setFiles((prevFiles) => [...prevFiles, ...acceptedFiles]);
    }, []);

    const { getRootProps, getInputProps, isDragActive } = useDropzone({
        onDrop,
        accept: {
            'image/*': ['.jpeg', '.jpg', '.png', '.gif'],
        },
        multiple: true,
    });

    const handleUpload = async () => {
        setUploading(true);
        
        for (const file of files) {
            const formData = new FormData();
            formData.append('image', file);

            try {
                setUploadProgress((prev) => ({
                    ...prev,
                    [file.name]: 0,
                }));

                const response = await axios.post('/api/images', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );
                        setUploadProgress((prev) => ({
                            ...prev,
                            [file.name]: percentCompleted,
                        }));
                    },
                });

                setUploadProgress((prev) => ({
                    ...prev,
                    [file.name]: 100,
                }));
            } catch (error) {
                console.error('Upload error:', error);
                setUploadProgress((prev) => ({
                    ...prev,
                    [file.name]: -1, // Error state
                }));
            }
        }

        setUploading(false);
    };

    const removeFile = (fileName) => {
        setFiles((prevFiles) => prevFiles.filter((file) => file.name !== fileName));
        setUploadProgress((prev) => {
            const newProgress = { ...prev };
            delete newProgress[fileName];
            return newProgress;
        });
    };

    return (
        <div className="space-y-6">
            {/* Drop Zone */}
            <div
                {...getRootProps()}
                className={`border-2 border-dashed rounded-lg p-8 text-center cursor-pointer transition-colors duration-200 ${
                    isDragActive
                        ? 'border-blue-500 bg-blue-50'
                        : 'border-gray-300 hover:border-gray-400'
                }`}
            >
                <input {...getInputProps()} />
                <div className="space-y-4">
                    <svg
                        className="mx-auto h-12 w-12 text-gray-400"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                        />
                    </svg>
                    <div className="text-gray-600">
                        {isDragActive ? (
                            <p className="text-blue-500">Drop the files here ...</p>
                        ) : (
                            <>
                                <p className="text-base">
                                    Drag 'n' drop some files here, or click to select files
                                </p>
                                <p className="text-sm text-gray-500">
                                    (Only *.jpeg, *.jpg, *.png, *.gif images will be accepted)
                                </p>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* File List */}
            {files.length > 0 && (
                <div className="bg-white shadow sm:rounded-lg overflow-hidden">
                    <ul className="divide-y divide-gray-200">
                        {files.map((file) => (
                            <li key={file.name} className="p-4">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center space-x-4">
                                        <div className="flex-shrink-0 h-10 w-10">
                                            <img
                                                className="h-10 w-10 rounded object-cover"
                                                src={URL.createObjectURL(file)}
                                                alt={file.name}
                                            />
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-gray-900 truncate">
                                                {file.name}
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                {(file.size / 1024 / 1024).toFixed(2)} MB
                                            </p>
                                        </div>
                                    </div>
                                    <div className="flex items-center space-x-4">
                                        {uploadProgress[file.name] === undefined ? (
                                            <button
                                                onClick={() => removeFile(file.name)}
                                                className="text-red-600 hover:text-red-900"
                                            >
                                                Remove
                                            </button>
                                        ) : uploadProgress[file.name] === 100 ? (
                                            <svg
                                                className="h-5 w-5 text-green-500"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                        ) : uploadProgress[file.name] === -1 ? (
                                            <svg
                                                className="h-5 w-5 text-red-500"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={2}
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        ) : (
                                            <div className="w-16 bg-gray-200 rounded-full h-2">
                                                <div
                                                    className="bg-blue-600 h-2 rounded-full"
                                                    style={{
                                                        width: `${uploadProgress[file.name]}%`,
                                                    }}
                                                ></div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {/* Upload Button */}
            {files.length > 0 && (
                <div className="flex justify-end">
                    <button
                        onClick={handleUpload}
                        disabled={uploading}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                    >
                        {uploading ? (
                            <>
                                <svg
                                    className="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <circle
                                        className="opacity-25"
                                        cx="12"
                                        cy="12"
                                        r="10"
                                        stroke="currentColor"
                                        strokeWidth="4"
                                    ></circle>
                                    <path
                                        className="opacity-75"
                                        fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                    ></path>
                                </svg>
                                Uploading...
                            </>
                        ) : (
                            'Upload Files'
                        )}
                    </button>
                </div>
            )}
        </div>
    );
} 