{{-- Partial Reusable untuk Menampilkan Jadwal per Semester --}}
@php
    $baris = $jadwal[$sem] ?? collect();
    $perHari = $baris->groupBy('hari');
    $hariList = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];
    $totalJadwal = $baris->count();
@endphp

<div class="semester-section" data-semester="{{ $sem }}" id="section-sem-{{ $sem }}" style="margin-bottom: 22px; border: 1.5px solid #e2e8f0; border-radius: 12px; background: #ffffff; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
    {{-- Header Accordion --}}
    <div class="semester-accordion-header" onclick="toggleAccordion('{{ $sem }}')" 
        style="display: flex; justify-content: space-between; align-items: center; padding: 14px 20px; background: #f8fafc; border-bottom: 1.5px solid #e2e8f0; cursor: pointer; user-select: none; transition: background 0.2s ease;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px; background: #0860be; color: #ffffff; border-radius: 8px; font-weight: 700; font-size: 0.95rem;">
                {{ $sem }}
            </span>
            <div>
                <h3 style="margin: 0; font-size: 1.1rem; color: #0f172a; font-weight: 700;">Semester {{ $sem }}</h3>
                <span style="font-size: 0.85rem; color: #64748b;">
                    @if ($totalJadwal > 0)
                        {{ $totalJadwal }} kelas terjadwal
                    @else
                        Belum ada kelas terjadwal
                    @endif
                </span>
            </div>
        </div>
        
        <div style="display: flex; align-items: center; gap: 10px;">
            <span id="accordion-badge-{{ $sem }}" class="badge-status {{ $totalJadwal > 0 ? 'badge-aktif' : 'badge-kosong' }}" 
                style="padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; {{ $totalJadwal > 0 ? 'background: #e0f2fe; color: #0284c7;' : 'background: #f1f5f9; color: #94a3b8;' }}">
                {{ $totalJadwal > 0 ? 'Terjadwal' : 'Kosong' }}
            </span>
            <button type="button" style="background: none; border: none; cursor: pointer; color: #64748b; padding: 4px; display: flex; align-items: center;">
                <svg id="chevron-sem-{{ $sem }}" class="accordion-chevron" style="width: 20px; height: 20px; transition: transform 0.25s ease;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </button>
        </div>
    </div>

    {{-- Body Accordion --}}
    <div class="semester-accordion-body" id="accordion-body-{{ $sem }}" style="padding: 16px 20px;">
        @if ($baris->isEmpty())
            <div style="padding: 32px 16px; text-align: center; color: #64748b; background: #fbfcfe; border-radius: 8px; border: 1px dashed #cbd5e1;">
                <svg viewBox="0 0 24 24" width="36" height="36" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 8px;">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
                <p style="margin: 0; font-size: 0.95rem; font-weight: 500;">Belum ada jadwal untuk Semester {{ $sem }}.</p>
                <span style="font-size: 0.85rem; color: #94a3b8;">Silakan buat jadwal melalui tab <strong style="color: #0860be; cursor: pointer;" onclick="document.querySelector('[data-tab=\'tab-generate\']').click();">Generate Jadwal</strong>.</span>
            </div>
        @else
            <div style="display: flex; flex-direction: column; gap: 20px;">
                @foreach ($hariList as $hari)
                    @if (($perHari[$hari] ?? collect())->isNotEmpty())
                        <div class="hari-jadwal-group">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                <div style="width: 8px; height: 8px; background: #0860be; border-radius: 50%;"></div>
                                <h4 style="margin: 0; font-size: 1rem; color: #1e3a5f; font-weight: 700;">{{ $hari }}</h4>
                                <span class="hari-count-badge" style="font-size: 0.8rem; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 12px;">{{ $perHari[$hari]->count() }} kelas</span>
                            </div>
                            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch; border-radius: 8px; border: 1px solid #e2e8f0;">
                                <table class="data-table" style="min-width: 760px; margin: 0;">
                                    <thead>
                                        <tr style="background: #f8fafc;">
                                            <th style="width: 130px;">Jam</th>
                                            <th style="width: 100px;">Kode MK</th>
                                            <th>Nama MK</th>
                                            <th style="width: 60px;">SKS</th>
                                            <th style="width: 80px;">Kelas</th>
                                            <th>Dosen Pengampu</th>
                                            <th style="width: 100px;">Ruangan</th>
                                            <th style="width: 110px;">Prodi</th>
                                            <th style="width: 80px;">Tipe</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($perHari[$hari] as $j)
                                            @php
                                                $cleanKodeMk = preg_replace('/-[A-Za-z0-9]+$/', '', $j->kode_mk ?? '');
                                                $kelasLabel = $j->rombel_kelas ?? $j->mk_kelas ?? null;
                                                if (! $kelasLabel && preg_match('/-([A-Za-z0-9]+)$/', $j->kode_rombel ?? '', $matches)) {
                                                    $kelasLabel = $matches[1];
                                                }
                                                if (! $kelasLabel && preg_match('/-([A-Za-z0-9]+)$/', $j->kode_mk ?? '', $matches)) {
                                                    $kelasLabel = $matches[1];
                                                }
                                                $kelasLabel = strtoupper(trim((string) ($kelasLabel ?: 'A')));
                                                $dosenInisial = ! empty($j->inisial_dosen) ? $j->inisial_dosen : (\App\Models\Dosen::generateInisialFromName($j->nama_dosen) ?: '-');
                                            @endphp
                                            <tr class="jadwal-row-item" data-prodi="{{ $j->prodi }}">
                                                <td style="white-space: nowrap; font-weight: 600; color: #0860be;">
                                                    {{ substr($j->jam_mulai, 0, 5) }} - {{ substr($j->jam_selesai, 0, 5) }}
                                                </td>
                                                <td style="white-space: nowrap;"><strong>{{ $cleanKodeMk }}</strong></td>
                                                <td>{{ $j->nama_mk }}</td>
                                                <td><span style="display: inline-block; padding: 2px 8px; background: #e2e8f0; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">{{ $j->sks }}</span></td>
                                                <td>
                                                    <span style="display: inline-block; padding: 2px 8px; background: #dbeafe; color: #1e40af; border-radius: 12px; font-size: 0.8rem; font-weight: 700;">
                                                        {{ $kelasLabel }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span title="{{ $j->nama_dosen ?? '-' }}" style="font-weight: 700; color: #1e3a5f; cursor: help; background: #f8fafc; padding: 3px 8px; border-radius: 6px; font-size: 0.85rem; display: inline-block; border: 1px solid #cbd5e1;">
                                                        {{ $dosenInisial }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if($j->kode_ruangan)
                                                        <span style="display: inline-block; padding: 3px 8px; background: #ecfdf5; color: #047857; font-weight: 600; border-radius: 6px; font-size: 0.85rem;">{{ $j->kode_ruangan }}</span>
                                                    @else
                                                        <span style="color: #94a3b8; font-style: italic; font-size: 0.85rem;">-</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span style="font-size: 0.75rem; padding: 2px 8px; border-radius: 10px; font-weight: 600; white-space: nowrap; {{ str_contains(strtolower($j->prodi ?? ''), 'informatika') ? 'background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;' : 'background: #fef3c7; color: #b45309; border: 1px solid #fde68a;' }}">
                                                        {{ $j->prodi ?? '-' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span style="font-size: 0.8rem; padding: 2px 8px; border-radius: 4px; font-weight: 600; {{ $j->tipe === 'Wajib' ? 'background: #eff6ff; color: #1d4ed8;' : 'background: #fef3c7; color: #b45309;' }}">
                                                        {{ $j->tipe }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>
