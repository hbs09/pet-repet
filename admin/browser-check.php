<?php
// Arquivo para verificação do ambiente do navegador
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico do Navegador - Pet & Repet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 40px;
            background: #f7f7f7;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        h1, h2 {
            color: #333;
        }
        
        .result-section {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        
        .success {
            color: green;
        }
        
        .warning {
            color: orange;
        }
        
        .error {
            color: red;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        table, th, td {
            border: 1px solid #ddd;
        }
        
        th, td {
            padding: 8px;
            text-align: left;
        }
        
        th {
            background-color: #f2f2f2;
        }
        
        #testToastify {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 10px 0;
            cursor: pointer;
            border-radius: 4px;
        }
    </style>
    <!-- Carregando Toastify para teste -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
</head>
<body>
    <div class="container">
        <h1>Diagnóstico do Navegador</h1>
        <p>Esta página analisa o ambiente do seu navegador para detectar possíveis problemas com o Toastify.</p>
        
        <div class="result-section">
            <h2>Informações do Navegador</h2>
            <div id="browserInfo">Carregando...</div>
        </div>
        
        <div class="result-section">
            <h2>Status do JavaScript</h2>
            <div id="jsStatus">Carregando...</div>
        </div>
        
        <div class="result-section">
            <h2>Status do Toastify</h2>
            <div id="toastifyStatus">Carregando...</div>
            <button id="testToastify">Testar Toastify</button>
        </div>
        
        <div class="result-section">
            <h2>Cookies e Local Storage</h2>
            <div id="storageStatus">Carregando...</div>
        </div>
        
        <div class="result-section">
            <h2>CORS e Recursos Externos</h2>
            <div id="corsStatus">Carregando...</div>
        </div>
        
        <div class="result-section">
            <h2>Status de Conexão</h2>
            <div id="connectionStatus">Carregando...</div>
        </div>
        
        <div class="result-section">
            <h2>Extensões e Bloqueadores</h2>
            <div id="extensionsStatus">Carregando...</div>
        </div>
    </div>
    
    <script>
        // Função para verificar o navegador
        function checkBrowser() {
            const browserInfo = document.getElementById('browserInfo');
            const ua = navigator.userAgent;
            const browserName = (function() {
                if (ua.indexOf("Edge") > -1) return "Microsoft Edge";
                if (ua.indexOf("Edg") > -1) return "Microsoft Edge (Chromium)";
                if (ua.indexOf("Chrome") > -1) return "Google Chrome";
                if (ua.indexOf("Safari") > -1) return "Safari";
                if (ua.indexOf("Firefox") > -1) return "Firefox";
                if (ua.indexOf("MSIE") > -1 || ua.indexOf("Trident") > -1) return "Internet Explorer";
                return "Desconhecido";
            })();
            
            browserInfo.innerHTML = `
                <p><strong>User Agent:</strong> ${ua}</p>
                <p><strong>Navegador detectado:</strong> ${browserName}</p>
                <p><strong>Versão:</strong> ${navigator.appVersion}</p>
                <p><strong>Plataforma:</strong> ${navigator.platform}</p>
                <p><strong>Idioma:</strong> ${navigator.language}</p>
            `;
        }
        
        // Verificar status do JavaScript
        function checkJavaScript() {
            const jsStatus = document.getElementById('jsStatus');
            jsStatus.innerHTML = `
                <p class="success">JavaScript está habilitado (você não veria esta mensagem caso contrário).</p>
                <p><strong>Versão ECMAScript:</strong> Verificando...</p>
            `;
            
            try {
                // Testando recursos de ES6+
                const features = [];
                try { eval("() => {}"); features.push("Arrow functions (ES6)"); } catch(e) {}
                try { eval("class Test {}"); features.push("Classes (ES6)"); } catch(e) {}
                try { eval("let a = 1"); features.push("let/const (ES6)"); } catch(e) {}
                try { eval("new Promise(() => {})"); features.push("Promises (ES6)"); } catch(e) {}
                try { eval("async () => {}"); features.push("Async/Await (ES8)"); } catch(e) {}
                
                jsStatus.innerHTML += `
                    <p><strong>Recursos suportados:</strong></p>
                    <ul>
                        ${features.map(f => `<li class="success">${f}</li>`).join('')}
                    </ul>
                `;
            } catch (e) {
                jsStatus.innerHTML += `
                    <p class="error">Erro ao testar recursos do JavaScript: ${e.message}</p>
                `;
            }
        }
        
        // Verificar status do Toastify
        function checkToastify() {
            const toastifyStatus = document.getElementById('toastifyStatus');
            
            if (typeof Toastify === 'function') {
                toastifyStatus.innerHTML = `
                    <p class="success">Toastify está carregado corretamente!</p>
                    <p><strong>Tipo:</strong> ${typeof Toastify}</p>
                `;
                
                // Verificar se o CSS do Toastify está carregado
                let cssLoaded = false;
                for (let i = 0; i < document.styleSheets.length; i++) {
                    try {
                        if (document.styleSheets[i].href && document.styleSheets[i].href.includes('toastify')) {
                            cssLoaded = true;
                            break;
                        }
                    } catch (e) {
                        // Alguns navegadores bloqueiam acesso a styleSheets de origens diferentes
                        console.log("Não foi possível verificar stylesheet:", e);
                    }
                }
                
                toastifyStatus.innerHTML += `
                    <p class="${cssLoaded ? 'success' : 'warning'}">CSS do Toastify: ${cssLoaded ? 'Detectado' : 'Não detectado (pode estar bloqueado ou não carregado)'}</p>
                `;
                
                // Teste a visibilidade dos elementos Toastify
                toastifyStatus.innerHTML += `
                    <p>Testando visibilidade do Toastify...</p>
                `;
            } else {
                toastifyStatus.innerHTML = `
                    <p class="error">Toastify não está carregado! (typeof Toastify = ${typeof Toastify})</p>
                    <p>Possíveis causas:</p>
                    <ul>
                        <li>O script não foi carregado corretamente</li>
                        <li>Há um bloqueador impedindo o carregamento</li>
                        <li>Há um erro de JavaScript que impede a execução</li>
                    </ul>
                `;
            }
        }
        
        // Verificar cookies e local storage
        function checkStorage() {
            const storageStatus = document.getElementById('storageStatus');
            let cookiesEnabled = navigator.cookieEnabled;
            
            let localStorageEnabled = false;
            try {
                localStorage.setItem('test', 'test');
                localStorage.removeItem('test');
                localStorageEnabled = true;
            } catch (e) {
                localStorageEnabled = false;
            }
            
            let sessionStorageEnabled = false;
            try {
                sessionStorage.setItem('test', 'test');
                sessionStorage.removeItem('test');
                sessionStorageEnabled = true;
            } catch (e) {
                sessionStorageEnabled = false;
            }
            
            storageStatus.innerHTML = `
                <p><strong>Cookies:</strong> <span class="${cookiesEnabled ? 'success' : 'error'}">${cookiesEnabled ? 'Habilitados' : 'Desabilitados'}</span></p>
                <p><strong>Local Storage:</strong> <span class="${localStorageEnabled ? 'success' : 'error'}">${localStorageEnabled ? 'Habilitado' : 'Desabilitado'}</span></p>
                <p><strong>Session Storage:</strong> <span class="${sessionStorageEnabled ? 'success' : 'error'}">${sessionStorageEnabled ? 'Habilitado' : 'Desabilitado'}</span></p>
            `;
        }
        
        // Verificar CORS e recursos externos
        function checkCORS() {
            const corsStatus = document.getElementById('corsStatus');
            corsStatus.innerHTML = '<p>Verificando acesso a recursos externos...</p>';
            
            const resources = [
                { name: 'CDN Toastify CSS', url: 'https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css', type: 'css' },
                { name: 'CDN Toastify JS', url: 'https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js', type: 'js' },
                { name: 'Google Fonts', url: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap', type: 'css' }
            ];
            
            const table = document.createElement('table');
            table.innerHTML = `
                <tr>
                    <th>Recurso</th>
                    <th>URL</th>
                    <th>Status</th>
                </tr>
            `;
            
            resources.forEach(resource => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${resource.name}</td>
                    <td>${resource.url}</td>
                    <td id="status-${resources.indexOf(resource)}">Verificando...</td>
                `;
                table.appendChild(row);
            });
            
            corsStatus.appendChild(table);
            
            resources.forEach((resource, index) => {
                const statusCell = document.getElementById(`status-${index}`);
                
                fetch(resource.url, { method: 'HEAD', mode: 'no-cors' })
                    .then(() => {
                        // O resultado no-cors sempre resolve, mas não podemos acessar os detalhes da resposta
                        // Verificamos se o recurso está carregado olhando para os elementos da página
                        let loaded = false;
                        
                        if (resource.type === 'css') {
                            for (let i = 0; i < document.styleSheets.length; i++) {
                                try {
                                    if (document.styleSheets[i].href && document.styleSheets[i].href.includes(resource.url)) {
                                        loaded = true;
                                        break;
                                    }
                                } catch (e) {}
                            }
                        } else if (resource.type === 'js') {
                            // Para JS, só podemos verificar se o script existe no DOM, não se foi executado corretamente
                            const scripts = document.getElementsByTagName('script');
                            for (let i = 0; i < scripts.length; i++) {
                                if (scripts[i].src && scripts[i].src.includes(resource.url)) {
                                    loaded = true;
                                    break;
                                }
                            }
                        }
                        
                        statusCell.innerHTML = `<span class="${loaded ? 'success' : 'warning'}">${loaded ? 'Carregado' : 'Requisição enviada, mas status desconhecido'}</span>`;
                    })
                    .catch(error => {
                        statusCell.innerHTML = `<span class="error">Erro: ${error.message}</span>`;
                    });
            });
        }
        
        // Verificar status de conexão
        function checkConnection() {
            const connectionStatus = document.getElementById('connectionStatus');
            
            if ('onLine' in navigator) {
                connectionStatus.innerHTML = `
                    <p><strong>Status de Conexão:</strong> <span class="${navigator.onLine ? 'success' : 'error'}">${navigator.onLine ? 'Online' : 'Offline'}</span></p>
                `;
            } else {
                connectionStatus.innerHTML = `
                    <p class="warning">Não foi possível determinar o status de conexão.</p>
                `;
            }
            
            // Adicionar informações de network timing
            if (window.performance && window.performance.timing) {
                const timing = window.performance.timing;
                const navigationStart = timing.navigationStart;
                connectionStatus.innerHTML += `
                    <p><strong>Tempo de Carregamento:</strong></p>
                    <ul>
                        <li>Início da Navegação até Agora: ${Date.now() - navigationStart}ms</li>
                        <li>DOM Carregado: ${timing.domComplete - navigationStart}ms</li>
                        <li>Tempo de Conexão: ${timing.connectEnd - timing.connectStart}ms</li>
                        <li>Tempo de DNS: ${timing.domainLookupEnd - timing.domainLookupStart}ms</li>
                    </ul>
                `;
            }
        }
        
        // Verificar extensões e bloqueadores
        function checkExtensions() {
            const extensionsStatus = document.getElementById('extensionsStatus');
            extensionsStatus.innerHTML = '<p>Verificando possíveis bloqueadores de conteúdo...</p>';
            
            const checks = [];
            
            // Verificar se há bloqueadores de anúncios
            const checkAdBlocker = new Promise(resolve => {
                const testAd = document.createElement('div');
                testAd.innerHTML = '&nbsp;';
                testAd.className = 'adsbox';
                document.body.appendChild(testAd);
                window.setTimeout(function() {
                    if (testAd.offsetHeight === 0) {
                        checks.push('<li class="warning">Bloqueador de anúncios detectado</li>');
                    } else {
                        checks.push('<li class="success">Nenhum bloqueador de anúncios detectado</li>');
                    }
                    testAd.remove();
                    resolve();
                }, 100);
            });
            
            // Verificar se há bloqueadores de scripts
            const checkScriptBlocker = new Promise(resolve => {
                const script = document.createElement('script');
                script.type = 'text/javascript';
                script.src = 'https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js';
                script.onload = function() {
                    checks.push('<li class="success">Scripts externos são permitidos</li>');
                    resolve();
                };
                script.onerror = function() {
                    checks.push('<li class="warning">Possível bloqueador de scripts detectado</li>');
                    resolve();
                };
                document.body.appendChild(script);
                
                // Timeout caso o script não carregue nem dispare erro
                setTimeout(function() {
                    if (!window.jQuery) {
                        checks.push('<li class="warning">Timeout ao carregar script externo</li>');
                        resolve();
                    }
                }, 3000);
            });
            
            // Verificar permissões de notificações (relevante para toasts)
            const checkNotifications = new Promise(resolve => {
                if ('Notification' in window) {
                    checks.push(`<li><strong>API de Notificações:</strong> <span class="success">Disponível</span></li>`);
                    checks.push(`<li><strong>Permissão de Notificações:</strong> <span class="${Notification.permission === 'granted' ? 'success' : 'warning'}">${Notification.permission}</span></li>`);
                } else {
                    checks.push('<li><strong>API de Notificações:</strong> <span class="error">Não disponível</span></li>');
                }
                resolve();
            });
            
            // Verificar para Content Security Policy (CSP)
            const checkCSP = new Promise(resolve => {
                if ('securitypolicyviolation' in document) {
                    checks.push('<li><strong>Content Security Policy:</strong> <span class="success">API Disponível</span></li>');
                } else {
                    checks.push('<li><strong>Content Security Policy:</strong> <span class="warning">API não disponível</span></li>');
                }
                resolve();
            });
            
            Promise.all([checkAdBlocker, checkScriptBlocker, checkNotifications, checkCSP]).then(() => {
                extensionsStatus.innerHTML = `
                    <p>Resultados da verificação:</p>
                    <ul>
                        ${checks.join('')}
                    </ul>
                    <p class="warning">Nota: Se você está usando extensões como NoScript, uBlock Origin, Privacy Badger ou similares, elas podem interferir na exibição do Toastify.</p>
                `;
            });
        }
        
        // Inicializar todas as verificações
        document.addEventListener('DOMContentLoaded', function() {
            checkBrowser();
            checkJavaScript();
            checkToastify();
            checkStorage();
            checkCORS();
            checkConnection();
            checkExtensions();
            
            // Botão para testar Toastify
            document.getElementById('testToastify').addEventListener('click', function() {
                if (typeof Toastify === 'function') {
                    try {
                        Toastify({
                            text: "Teste de Toastify - Se você vê esta mensagem, o Toastify está funcionando!",
                            duration: 5000,
                            gravity: "top",
                            position: "center",
                            backgroundColor: "linear-gradient(to right, #00b09b, #96c93d)",
                            stopOnFocus: true,
                        }).showToast();
                        
                        document.getElementById('toastifyStatus').innerHTML += `
                            <p class="success">Toastify executado com sucesso! Você deve ver uma notificação.</p>
                        `;
                    } catch (error) {
                        document.getElementById('toastifyStatus').innerHTML += `
                            <p class="error">Erro ao executar Toastify: ${error.message}</p>
                            <pre>${error.stack}</pre>
                        `;
                    }
                } else {
                    alert("Toastify não está disponível. Verifique o console para mais informações.");
                }
            });
        });
    </script>
</body>
</html>
