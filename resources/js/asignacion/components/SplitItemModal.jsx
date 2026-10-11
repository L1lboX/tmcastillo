import { useState, useId } from 'react';
import { createPortal } from 'react-dom';

export function SplitItemModal({ item, envio, isOpen = true, onClose, onConfirm }) {
    if (!isOpen || !item) return null;

    const baseId = useId();
    const totalCantidad = Number(item.cantidad) || 0;

    const [mode, setMode] = useState('equal'); // 'equal' | 'custom'
    const [numPartes, setNumPartes] = useState(2);
    const [customParts, setCustomParts] = useState([
        Math.floor(totalCantidad / 2) || 1,
        totalCantidad - (Math.floor(totalCantidad / 2) || 1),
    ]);
    const [submitting, setSubmitting] = useState(false);
    const [errorMsg, setErrorMsg] = useState(null);

    const calculateEqual = (n) => {
        const parts = Math.max(2, Math.min(n, totalCantidad));
        const base = Math.floor(totalCantidad / parts);
        const remainder = totalCantidad % parts;
        const res = [];
        for (let i = 0; i < parts; i++) {
            res.push(base + (i < remainder ? 1 : 0));
        }
        return res;
    };

    const equalDistribution = calculateEqual(numPartes);

    const customSum = customParts.reduce((acc, curr) => acc + (Number(curr) || 0), 0);
    const customDiff = totalCantidad - customSum;
    const isCustomValid = customSum === totalCantidad && customParts.every((p) => Number(p) > 0);

    const activeDistribution = mode === 'equal' 
        ? equalDistribution 
        : customParts.map((p) => Number(p) || 0);

    const handleAddPart = () => {
        if (customDiff > 0) {
            setCustomParts([...customParts, customDiff]);
        } else {
            setCustomParts([...customParts, 1]);
        }
    };

    const handleRemovePart = (index) => {
        if (customParts.length <= 2) return;
        setCustomParts(customParts.filter((_, idx) => idx !== index));
    };

    const handleCustomChange = (index, valStr) => {
        const val = parseInt(valStr, 10);
        const copy = [...customParts];
        copy[index] = isNaN(val) ? '' : Math.max(0, val);
        setCustomParts(copy);
    };

    const handleSubmit = async () => {
        setErrorMsg(null);
        const cantidades = mode === 'equal' 
            ? equalDistribution 
            : customParts.map((p) => Number(p));

        const suma = cantidades.reduce((a, b) => a + b, 0);
        if (suma !== totalCantidad) {
            setErrorMsg(`La suma de las partes debe dar exactamente ${totalCantidad}. Actual: ${suma}.`);
            return;
        }

        if (cantidades.some((q) => q <= 0 || isNaN(q))) {
            setErrorMsg('Todas las partes deben ser de al menos 1 unidad.');
            return;
        }

        try {
            setSubmitting(true);
            await onConfirm(item.id, cantidades);
            onClose();
        } catch (err) {
            setErrorMsg(err.message || 'Error al procesar la división');
        } finally {
            setSubmitting(false);
        }
    };

    const modalContent = (
        <div className="cargo-split-backdrop" onClick={onClose}>
            <div className="cargo-split-dialog" onClick={(e) => e.stopPropagation()}>
                
                <div className="cargo-split-header">
                    <div className="cargo-split-header-info">
                        <span className="cargo-split-badge">Operación de Carga</span>
                        <h3>Fraccionar Lote de Producto</h3>
                        <p>Envío: <strong>{envio.codigo || `#${envio.id}`}</strong> &bull; {envio.cliente}</p>
                    </div>
                    <button type="button" className="cargo-split-close" onClick={onClose} aria-label="Cerrar">
                        &times;
                    </button>
                </div>

                <div className="cargo-split-body">
                    
                    <div className="cargo-origin-card">
                        <div className="cargo-origin-main">
                            <span className="cargo-origin-label">Producto a dividir</span>
                            <strong className="cargo-origin-desc">{item.descripcion}</strong>
                        </div>
                        <div className="cargo-origin-qty">
                            <span className="cargo-origin-label">Total disponible</span>
                            <span className="cargo-origin-number">{totalCantidad}</span>
                        </div>
                    </div>

                    {/* Switch de Modo */}
                    <div className="cargo-switch-tabs">
                        <button
                            type="button"
                            className={`cargo-tab-btn ${mode === 'equal' ? 'active' : ''}`}
                            onClick={() => setMode('equal')}
                        >
                            División Equitativa
                        </button>
                        <button
                            type="button"
                            className={`cargo-tab-btn ${mode === 'custom' ? 'active' : ''}`}
                            onClick={() => setMode('custom')}
                        >
                            Cantidades Manuales
                        </button>
                    </div>

                    {mode === 'equal' ? (
                        <div className="cargo-mode-box">
                            <label className="cargo-control-label" htmlFor={`${baseId}-partes`}>
                                Selecciona el número de fracciones:
                            </label>
                            
                            <div className="cargo-counter-container">
                                <div className="cargo-counter-control">
                                    <button
                                        type="button"
                                        className="cargo-counter-btn"
                                        onClick={() => setNumPartes((p) => Math.max(2, p - 1))}
                                        disabled={numPartes <= 2}
                                    >
                                        &minus;
                                    </button>
                                    <div className="cargo-counter-display">
                                        <input
                                            id={`${baseId}-partes`}
                                            type="number"
                                            value={numPartes}
                                            onChange={(e) => {
                                                const v = parseInt(e.target.value, 10) || 2;
                                                setNumPartes(Math.max(2, Math.min(totalCantidad, v)));
                                            }}
                                        />
                                        <span className="cargo-counter-unit">partes</span>
                                    </div>
                                    <button
                                        type="button"
                                        className="cargo-counter-btn"
                                        onClick={() => setNumPartes((p) => Math.min(totalCantidad, p + 1))}
                                        disabled={numPartes >= totalCantidad}
                                    >
                                        &#43;
                                    </button>
                                </div>

                                <div className="cargo-presets-group">
                                    <button 
                                        type="button" 
                                        className={`cargo-preset-chip ${numPartes === 2 ? 'active' : ''}`}
                                        onClick={() => setNumPartes(2)}
                                    >
                                        Mitad (2)
                                    </button>
                                    {totalCantidad >= 3 && (
                                        <button 
                                            type="button" 
                                            className={`cargo-preset-chip ${numPartes === 3 ? 'active' : ''}`}
                                            onClick={() => setNumPartes(3)}
                                        >
                                            3 partes
                                        </button>
                                    )}
                                    {totalCantidad >= 4 && (
                                        <button 
                                            type="button" 
                                            className={`cargo-preset-chip ${numPartes === 4 ? 'active' : ''}`}
                                            onClick={() => setNumPartes(4)}
                                        >
                                            4 partes
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    ) : (
                        /* Modo: Manual */
                        <div className="cargo-mode-box">
                            <div className="cargo-custom-header">
                                <span className="cargo-control-label">Define la carga para cada fracción:</span>
                                <span className={`cargo-status-pill ${customDiff === 0 ? 'valid' : 'invalid'}`}>
                                    {customDiff === 0 
                                        ? 'Cuadre Exacto' 
                                        : customDiff > 0 
                                            ? `Faltan ${customDiff}` 
                                            : `Sobran ${Math.abs(customDiff)}`
                                    }
                                </span>
                            </div>

                            <div className="cargo-custom-grid">
                                {customParts.map((qty, idx) => (
                                    <div key={idx} className="cargo-custom-row">
                                        <span className="cargo-row-num">#{idx + 1}</span>
                                        <div className="cargo-input-wrap">
                                            <input
                                                type="number"
                                                value={qty}
                                                placeholder="0"
                                                onChange={(e) => handleCustomChange(idx, e.target.value)}
                                            />
                                        </div>
                                        {customParts.length > 2 && (
                                            <button
                                                type="button"
                                                className="cargo-row-remove"
                                                onClick={() => handleRemovePart(idx)}
                                                title="Eliminar fracción"
                                            >
                                                &times;
                                            </button>
                                        )}
                                    </div>
                                ))}
                            </div>

                            <button
                                type="button"
                                className="cargo-add-row-btn"
                                onClick={handleAddPart}
                                disabled={customDiff <= 0}
                            >
                                + Agregar otra fracción
                            </button>
                        </div>
                    )}

                    <div className="cargo-visual-preview">
                        <span className="cargo-preview-heading">
                            Tarjetas que aparecerán en el tablero ({activeDistribution.length}):
                        </span>
                        <div className="cargo-cards-flow">
                            {activeDistribution.map((cant, idx) => (
                                <div key={idx} className="cargo-mini-card">
                                    <span className="cargo-mini-idx">Fracción {idx + 1}</span>
                                    <strong className="cargo-mini-qty">{cant}</strong>
                                </div>
                            ))}
                        </div>
                    </div>

                    {errorMsg && (
                        <div className="cargo-alert-error">
                            {errorMsg}
                        </div>
                    )}
                </div>

                <div className="cargo-split-footer">
                    <button type="button" className="cargo-btn-secondary" onClick={onClose} disabled={submitting}>
                        Cancelar
                    </button>
                    <button
                        type="button"
                        className="cargo-btn-primary"
                        onClick={handleSubmit}
                        disabled={submitting || (mode === 'custom' && !isCustomValid)}
                    >
                        {submitting ? 'Generando fracciones...' : `Dividir en ${activeDistribution.length} fracciones`}
                    </button>
                </div>

            </div>
        </div>
    );

    return createPortal(modalContent, document.body);
}