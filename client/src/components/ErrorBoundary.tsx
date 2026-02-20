import React from 'react';

interface ErrorBoundaryState {
  hasError: boolean;
  error: Error | null;
  errorInfo: React.ErrorInfo | null;
}

export class ErrorBoundary extends React.Component<
  { children: React.ReactNode },
  ErrorBoundaryState
> {
  constructor(props: { children: React.ReactNode }) {
    super(props);
    this.state = { hasError: false, error: null, errorInfo: null };
  }

  static getDerivedStateFromError(error: Error) {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, errorInfo: React.ErrorInfo) {
    console.error('[ErrorBoundary]', error, errorInfo);
    this.setState({ errorInfo });
  }

  render() {
    if (this.state.hasError) {
      return (
        <div style={{
          minHeight: '100vh',
          background: '#1a1a2e',
          color: '#fff',
          display: 'flex',
          flexDirection: 'column',
          alignItems: 'center',
          justifyContent: 'center',
          padding: '2rem',
          fontFamily: 'monospace',
        }}>
          <h1 style={{ color: '#eab308', fontSize: '1.5rem', marginBottom: '1rem' }}>
            Errore nell'applicazione
          </h1>
          <div style={{
            background: '#2d2d44',
            border: '1px solid #ef4444',
            borderRadius: '8px',
            padding: '1rem',
            maxWidth: '800px',
            width: '100%',
            overflow: 'auto',
            maxHeight: '400px',
          }}>
            <p style={{ color: '#ef4444', fontWeight: 'bold', marginBottom: '0.5rem' }}>
              {this.state.error?.message}
            </p>
            <pre style={{ fontSize: '0.75rem', color: '#aaa', whiteSpace: 'pre-wrap' }}>
              {this.state.error?.stack}
            </pre>
            {this.state.errorInfo && (
              <pre style={{ fontSize: '0.75rem', color: '#888', whiteSpace: 'pre-wrap', marginTop: '1rem' }}>
                {this.state.errorInfo.componentStack}
              </pre>
            )}
          </div>
          <button
            onClick={() => {
              this.setState({ hasError: false, error: null, errorInfo: null });
              window.location.href = '/';
            }}
            style={{
              marginTop: '1rem',
              background: '#eab308',
              color: '#000',
              border: 'none',
              padding: '0.75rem 2rem',
              borderRadius: '8px',
              fontWeight: 'bold',
              cursor: 'pointer',
            }}
          >
            Torna alla Home
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}
