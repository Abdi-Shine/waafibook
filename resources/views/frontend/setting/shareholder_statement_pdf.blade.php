<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $shareholder->name }} - Ownership Certificate</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0B5567',
                        'primary-dark': '#094555',
                        'primary-light': '#0D6A7F',
                        accent: '#A4D65E',
                        'accent-dark': '#8BC04C',
                        'accent-light': '#B5E077',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        @media print {
            .no-print {
                display: none !important;
            }
            .print-area {
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            body {
                background: white !important;
            }
        }
        
        .signature-line {
            border-bottom: 2px solid #e5e7eb;
            min-width: 250px;
            display: inline-block;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            font-weight: 900;
            color: rgba(11, 85, 103, 0.02);
            z-index: 0;
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
        }
        
        .letter-content {
            position: relative;
            z-index: 1;
        }

        .details-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }
    </style>
</head>
<body class="bg-slate-50/50 min-h-screen py-10 px-4">

    <!-- Action Bar (No-Print) -->
    <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="{{ route('capital-deposit') }}" class="flex items-center gap-2 text-primary font-bold hover:underline transition-all">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Registry
        </a>
        <div class="flex gap-3">
            <button onclick="window.print()" class="px-6 py-2.5 bg-primary text-white font-bold rounded-xl shadow-lg hover:shadow-primary/30 transition-all">
                Print Statement
            </button>
        </div>
    </div>

    <!-- Letter Area -->
    <div class="print-area max-w-4xl mx-auto bg-white shadow-2xl rounded-2xl p-12 relative overflow-hidden min-h-[1050px]">
        
        <!-- Watermark -->
        <div class="watermark">{{ strtoupper($company->name ?? 'HORNTECH LTD') }}</div>

        <div class="letter-content">
            <!-- Header -->
            <div class="flex justify-between items-start mb-10">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-16 bg-white border border-gray-100 rounded-xl flex items-center justify-center shadow-md overflow-hidden p-2">
                        @if($company && $company->logo)
                            <img src="{{ asset($company->logo) }}" class="w-full h-full object-contain" alt="Logo">
                        @else
                            <div class="w-full h-full bg-primary flex items-center justify-center">
                                <span class="text-white text-3xl font-black">{{ substr($company->name ?? 'B', 0, 1) }}</span>
                            </div>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight text-primary leading-none">{{ strtoupper($company->name ?? 'HORNTECH LTD') }}</h1>
                        <p class="text-xs font-bold text-accent mt-1 uppercase tracking-widest">{{ $company->business_type ?? 'Trading LLC' }}</p>
                        
                        <div class="mt-4 text-[11px] text-gray-500 font-medium space-y-1">
                            <p class="flex items-center gap-2">
                                <svg class="w-3 h-3 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                {{ $company->address ?? 'Main Office Address' }} {{ $company->city ? ', ' . $company->city : '' }} {{ $company->country ? ', ' . $company->country : '' }}
                            </p>
                            @if($company->email)
                            <p class="flex items-center gap-2">
                                <svg class="w-3 h-3 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                {{ $company->email }}
                            </p>
                            @endif
                            @if($company->phone || $company->mobile_number)
                            <p class="flex items-center gap-2">
                                <svg class="w-3 h-3 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                {{ $company->phone ?? $company->mobile_number }}
                            </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="text-right">
                    <div class="inline-block px-4 py-1.5 bg-accent text-white font-black text-[10px] rounded-lg mb-3 tracking-[0.2em]">
                        STATEMENT
                    </div>
                    <div class="text-[11px] text-gray-500 font-bold space-y-0.5">
                        <p>Date: <span class="text-gray-900">{{ date('F d, Y') }}</span></p>
                        <p>Ref: <span class="text-gray-900">SH-{{ date('Y') }}-{{ str_pad($shareholder->id, 3, '0', STR_PAD_LEFT) }}</span></p>
                    </div>
                </div>
            </div>

            <!-- Title Section -->
            <div class="text-center mb-12">
                <hr class="border-gray-100 mb-8">
                <h2 class="text-[20px] font-black tracking-tight text-gray-900 uppercase">Shareholder Ownership Certificate</h2>
                <div class="w-20 h-1 bg-gradient-to-r from-primary to-accent mx-auto mt-2 rounded-full"></div>
            </div>

            <!-- Salutation & Certification -->
            <div class="space-y-6 text-[13px] text-gray-700 leading-relaxed">
                <p class="font-bold">To Whom It May Concern,</p>
                
                <p>
                    This is to certify that <span class="font-black text-gray-900 underline">{{ $shareholder->name }}</span> is a registered shareholder of <span class="font-bold text-primary">{{ $company->name ?? '' }}</span>, a {{ $company->legal_form ?? 'limited liability company' }} duly registered and operating under jurisdictional corporate laws.
                </p>

                <!-- Details Grid Box -->
                @php 
                    $ownershipPct = $totalCapital > 0 ? ($shareholder->share_amount / $totalCapital) * 100 : 0;
                    $parValue = $company->par_value > 0 ? $company->par_value : 100;
                    $numShares = $shareholder->share_amount / $parValue;
                @endphp
                <div class="details-box p-8 my-8">
                    <div class="flex items-center gap-2 mb-6 border-b border-gray-100 pb-4">
                        <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <h3 class="font-black text-primary uppercase text-sm tracking-tight">Shareholding Details</h3>
                    </div>

                    <div class="grid grid-cols-2 gap-y-6 gap-x-12">
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Shareholder Name</p>
                            <p class="font-bold text-gray-900 uppercase">{{ $shareholder->name }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Shareholder ID</p>
                            <p class="font-bold text-gray-900">SH-{{ date('Y') }}-{{ str_pad($shareholder->id, 3, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Number of Shares</p>
                            <p class="font-black text-primary text-xl">{{ number_format($numShares, 0) }} Shares</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Percentage Ownership</p>
                            <p class="font-black text-accent text-xl">{{ number_format($ownershipPct, 2) }}%</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Investment Value</p>
                            <p class="font-bold text-gray-900 uppercase">{{ number_format($shareholder->share_amount, 2) }} {{ $company->currency ?? 'SAR' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-400 font-black uppercase tracking-widest mb-1">Par Value per Share</p>
                            <p class="font-bold text-gray-900 uppercase">{{ $company->currency ?? 'SAR' }} {{ number_format($parValue, 2) }}</p>
                        </div>
                    </div>
                </div>

                <p>
                    The shares referenced herein represent a legitimate ownership interest in {{ $company->name ?? 'the above entity' }} and confer upon the shareholder all rights, privileges, and obligations as stipulated in the Articles of Association.
                </p>

                <p>
                    This certificate is issued for official purposes and serves as formal documentation of the shareholder's equity position in the company as of the date mentioned above.
                </p>

                <!-- Important Notice -->
                <div class="bg-primary/10 border-l-4 border-accent p-5 rounded-r-xl">
                    <div class="flex gap-3">
                        <svg class="w-5 h-5 text-accent shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <p class="font-bold text-gray-900 text-xs mb-1">Important Notice</p>
                            <p class="text-[11px] text-gray-600">This document is confidential and intended solely for the use of the addressee. Any unauthorized disclosure, reproduction, or distribution is strictly prohibited.</p>
                        </div>
                    </div>
                </div>

                <p>Should you require any additional information or verification regarding this shareholding, please do not hesitate to contact our Investor Relations Department.</p>

                <p class="pt-4 font-bold">Sincerely yours,</p>

                <!-- Signatures -->
                <div class="grid grid-cols-2 gap-16 pt-16">
                    <div>
                        <div class="signature-line mb-4"></div>
                        <p class="font-black text-gray-900 text-[12px] uppercase tracking-tight">Managing Director</p>
                        <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">{{ $company->name ?? '' }}</p>
                    </div>
                    <div>
                        <div class="signature-line mb-4"></div>
                        <p class="font-black text-gray-900 text-[12px] uppercase tracking-tight">{{ $shareholder->name }}</p>
                        <p class="text-[11px] text-gray-500 font-bold uppercase tracking-wider">Shareholder Signature</p>
                    </div>
                </div>

                <!-- Footer / Official Seal Area -->
                <div class="mt-12 border-t border-b border-gray-200 py-10 flex items-center justify-between">
                    <div class="flex items-center gap-6">
                        <!-- Official Seal -->
                        <div class="w-24 h-24 rounded-full border-[5px] border-primary flex flex-col items-center justify-center text-center p-1">
                            <span class="text-[13px] font-black text-primary leading-none uppercase tracking-tighter">OFFICIAL</span>
                            <span class="text-[7px] font-bold text-primary/60 uppercase tracking-tighter mt-1 leading-none">COMPANY SEAL</span>
                        </div>
                        
                        <!-- Registration Details -->
                        <div class="text-[12px] leading-tight font-medium text-gray-500">
                            <p class="font-bold text-gray-700">Company Registration No.</p>
                            <p>{{ $company->registration_number ?? 'CR-1234567890' }}</p>
                        </div>
                    </div>

                    <div class="text-right text-[11px] text-gray-400 space-y-2">
                        <p class="font-medium">This document was generated electronically and is valid without signature.</p>
                        <p class="font-bold">Verification Code: <span class="text-primary font-black">BLK-{{ date('Y') }}-SH-{{ str_pad($shareholder->id, 3, '0', STR_PAD_LEFT) }}-VRF</span></p>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="text-center pt-10 pb-4 text-[11px] text-gray-400 font-medium">
                    <p class="mb-1 leading-relaxed">
                        {{ $company->name ?? '' }} | {{ $company->address ?? 'King Fahd Road, Al Olaya District, Riyadh 12211, Saudi Arabia' }}
                    </p>
                    <p class="tracking-wide">
                        Tel: {{ $company->phone ?? '+966 55 123 4567' }} | Email: {{ $company->email ?? 'info@horntech.com' }} | Web: {{ $company->website ?? 'www.horntech.com' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

