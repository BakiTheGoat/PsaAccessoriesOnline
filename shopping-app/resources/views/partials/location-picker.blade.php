{{--
    Delivery location picker. Search a place or click the map to drop a
    pin; the address textarea and hidden lat/lng inputs fill in
    automatically. Uses Leaflet + OpenStreetMap (free, no API key) for
    the map, and Nominatim (OpenStreetMap's free geocoding service) for
    search/reverse-geocoding.
--}}
<div>
    <label class="block text-sm font-medium mb-1">Delivery / pickup address</label>

    <input type="text" id="location-search" placeholder="Search for a place, street, or landmark..."
           class="w-full border rounded-md px-3 py-2 mb-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">

    <div id="location-map" class="w-full h-64 rounded-md border mb-2"></div>

    <textarea name="shipping_address" id="shipping_address" rows="2" required
              placeholder="Address fills in automatically once you pick a spot on the map — feel free to add more detail (unit number, landmark, etc.)"
              class="w-full border rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">{{ old('shipping_address') }}</textarea>

    <input type="hidden" name="delivery_latitude" id="delivery_latitude" value="{{ old('delivery_latitude') }}">
    <input type="hidden" name="delivery_longitude" id="delivery_longitude" value="{{ old('delivery_longitude') }}">
    <p class="text-xs text-gray-400 mt-1">Pinning a location on the map helps us deliver to the right place.</p>
</div>

@push('head')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

@push('scripts')
<script>
    (function () {
        // Default view: Phnom Penh, Cambodia — adjust to your city if different.
        const defaultLat = 11.5564;
        const defaultLng = 104.9282;

        const map = L.map('location-map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(map);

        let marker = null;

        function setMarker(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on('dragend', () => {
                    const pos = marker.getLatLng();
                    reverseGeocode(pos.lat, pos.lng);
                });
            }
            document.getElementById('delivery_latitude').value = lat;
            document.getElementById('delivery_longitude').value = lng;
        }

        async function reverseGeocode(lat, lng) {
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await res.json();
                if (data.display_name) {
                    document.getElementById('shipping_address').value = data.display_name;
                }
            } catch (e) {
                console.error('Could not look up that address', e);
            }
        }

        map.on('click', (e) => {
            setMarker(e.latlng.lat, e.latlng.lng);
            reverseGeocode(e.latlng.lat, e.latlng.lng);
        });

        // Search box — press Enter to look up a place.
        const searchInput = document.getElementById('location-search');
        searchInput.addEventListener('keydown', async (e) => {
            if (e.key !== 'Enter') return;
            e.preventDefault();

            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchInput.value)}&limit=1`);
                const results = await res.json();
                if (results.length > 0) {
                    const { lat, lon, display_name } = results[0];
                    map.setView([lat, lon], 16);
                    setMarker(parseFloat(lat), parseFloat(lon));
                    document.getElementById('shipping_address').value = display_name;
                } else {
                    alert('No results found for that search — try a different spelling or a nearby landmark.');
                }
            } catch (err) {
                console.error('Search failed', err);
            }
        });

        // If editing/re-submitting with old values, restore the pin.
        const oldLat = document.getElementById('delivery_latitude').value;
        const oldLng = document.getElementById('delivery_longitude').value;
        if (oldLat && oldLng) {
            map.setView([oldLat, oldLng], 16);
            setMarker(parseFloat(oldLat), parseFloat(oldLng));
        }
    })();
</script>
@endpush
