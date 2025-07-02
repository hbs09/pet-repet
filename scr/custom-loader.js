/**
 * Custom Loading Animation Manager
 * Manages the cat paw loading animation
 */

// Store the loader reference
let loaderOverlay = null;
let loadingText = "Processando...";

/**
 * Show the custom cat paw loading animation
 * @param {string} text - Optional text to display with the loader (defaults to "Processando...")
 * @return {object} - Loader object with a hideLoader method
 */
function showPawLoader(text = "Processando...") {
    // If a loader already exists, update its text and return it
    if (loaderOverlay) {
        const textElement = loaderOverlay.querySelector('.loading-text');
        if (textElement) {
            textElement.textContent = text;
        }
        return {
            hideLoader: hidePawLoader
        };
    }
    
    // Store the text for later reference
    loadingText = text;
    
    // Create the overlay
    loaderOverlay = document.createElement('div');
    loaderOverlay.className = 'loading-overlay';
    
    // Create the paw container
    const pawContainer = document.createElement('div');
    pawContainer.className = 'paw-container';
    
    // Create main pad
    const mainPad = document.createElement('div');
    mainPad.className = 'paw main-pad';
    pawContainer.appendChild(mainPad);
    
    // Create toe pads
    for (let i = 1; i <= 3; i++) {
        const toePad = document.createElement('div');
        toePad.className = `paw toe-pad toe-pad-${i}`;
        pawContainer.appendChild(toePad);
    }
    
    // Create loading text
    const textElement = document.createElement('div');
    textElement.className = 'loading-text';
    textElement.textContent = text;
    
    // Append elements to overlay
    loaderOverlay.appendChild(pawContainer);
    loaderOverlay.appendChild(textElement);
    
    // Add to body
    document.body.appendChild(loaderOverlay);
    
    // Return object with method to hide the loader
    return {
        hideLoader: hidePawLoader
    };
}

/**
 * Hide the custom cat paw loading animation
 */
function hidePawLoader() {
    if (loaderOverlay) {
        // Add a fade-out class
        loaderOverlay.style.opacity = '0';
        loaderOverlay.style.transition = 'opacity 0.3s ease';
        
        // Remove after animation
        setTimeout(() => {
            if (loaderOverlay && loaderOverlay.parentNode) {
                loaderOverlay.parentNode.removeChild(loaderOverlay);
                loaderOverlay = null;
            }
        }, 300);
    }
}

/**
 * Update the text of the current loader
 * @param {string} text - New text to display
 */
function updatePawLoaderText(text) {
    if (loaderOverlay) {
        const textElement = loaderOverlay.querySelector('.loading-text');
        if (textElement) {
            textElement.textContent = text;
        }
    }
}
