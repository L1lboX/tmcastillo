import { useState, useRef, useEffect } from 'react';

export function TransportistaSearch({ disponibles, activas, onAgregar }) {
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState(false);
    const ref = useRef(null);

    const activasIds = new Set(activas.map((t) => t.id));
    const filtrados = disponibles.filter(
        (t) => !activasIds.has(t.id) && t.nombre.toLowerCase().includes(query.toLowerCase())
    );

    useEffect(() => {
        function handleClick(e) {
            if (ref.current && !ref.current.contains(e.target)) {
                setOpen(false);
            }
        }
        document.addEventListener('mousedown', handleClick);
        return () => document.removeEventListener('mousedown', handleClick);
    }, []);

    function handleSelect(t) {
        onAgregar(t);
        setQuery('');
        setOpen(false);
    }

    return (
        <div className="transportista-search" ref={ref}>
            <input
                type="text"
                className="transportista-search-input"
                placeholder="+ Agregar transportista..."
                value={query}
                onChange={(e) => {
                    setQuery(e.target.value);
                    setOpen(true);
                }}
                onFocus={() => setOpen(true)}
            />
            {open && filtrados.length > 0 && (
                <div className="transportista-search-dropdown">
                    {filtrados.map((t) => (
                        <button
                            key={t.id}
                            className="transportista-search-option"
                            onClick={() => handleSelect(t)}
                        >
                            <span className="transportista-search-option-name">{t.nombre}</span>
                        </button>
                    ))}
                </div>
            )}
        </div>
    );
}
