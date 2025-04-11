class AdidasChatbot {
    constructor() {
        this.chatContainer = document.getElementById('chatbot-container');
        this.messagesContainer = document.getElementById('chat-messages');
        this.inputField = document.getElementById('chat-input');
        this.sendButton = document.getElementById('send-message');
        this.toggleButton = document.getElementById('chatbot-toggle');
        
        this.userId = localStorage.getItem('chatbot_user_id') || this.generateUserId();
        this.setupEventListeners();
        this.addWelcomeMessage();
    }

    generateUserId() {
        const userId = 'user_' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem('chatbot_user_id', userId);
        return userId;
    }

    setupEventListeners() {
        // Send message on button click
        this.sendButton.addEventListener('click', () => this.sendMessage());

        // Send message on Enter key
        this.inputField.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                this.sendMessage();
            }
        });

        // Toggle chatbot visibility
        this.toggleButton.addEventListener('click', () => {
            this.chatContainer.classList.toggle('hidden');
            if (!this.chatContainer.classList.contains('hidden')) {
                this.inputField.focus();
            }
        });
    }

    addWelcomeMessage() {
        const welcomeMessage = {
            message: "Hello! Welcome to Adidas. How can I help you today?",
            suggestions: [
                "Show me new arrivals",
                "Track my order",
                "Size guide",
                "Contact support"
            ]
        };
        this.displayBotMessage(welcomeMessage);
    }

    async sendMessage() {
        const message = this.inputField.value.trim();
        if (!message) return;

        // Display user message
        this.displayUserMessage(message);
        this.inputField.value = '';

        // Show typing indicator
        this.showTypingIndicator();

        try {
            const response = await fetch('/chatbot/chatbot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    user_id: this.userId
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();
            
            // Remove typing indicator
            this.hideTypingIndicator();

            // Display bot response
            this.displayBotMessage(data);

        } catch (error) {
            console.error('Error:', error);
            this.hideTypingIndicator();
            this.displayErrorMessage();
        }
    }

    displayUserMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'mb-4 text-right';
        
        const bubble = document.createElement('div');
        bubble.className = 'inline-block bg-black text-white px-4 py-2 rounded-lg max-w-[80%]';
        bubble.textContent = message;
        
        messageDiv.appendChild(bubble);
        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    displayBotMessage(data) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'mb-4';
        
        // Main message bubble
        const bubble = document.createElement('div');
        bubble.className = 'inline-block bg-gray-200 px-4 py-2 rounded-lg max-w-[80%]';
        bubble.textContent = data.message;
        messageDiv.appendChild(bubble);

        // Suggestions
        if (data.suggestions && data.suggestions.length > 0) {
            const suggestionsDiv = document.createElement('div');
            suggestionsDiv.className = 'mt-2 flex flex-wrap gap-2';
            
            data.suggestions.forEach(suggestion => {
                const suggestionButton = document.createElement('button');
                suggestionButton.className = 'text-sm bg-gray-100 hover:bg-gray-200 px-3 py-1 rounded-full transition-colors';
                suggestionButton.textContent = suggestion;
                suggestionButton.addEventListener('click', () => {
                    this.inputField.value = suggestion;
                    this.sendMessage();
                });
                suggestionsDiv.appendChild(suggestionButton);
            });
            
            messageDiv.appendChild(suggestionsDiv);
        }

        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    displayErrorMessage() {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'mb-4';
        
        const bubble = document.createElement('div');
        bubble.className = 'inline-block bg-red-100 text-red-700 px-4 py-2 rounded-lg';
        bubble.textContent = "Sorry, I'm having trouble connecting. Please try again later.";
        
        messageDiv.appendChild(bubble);
        this.messagesContainer.appendChild(messageDiv);
        this.scrollToBottom();
    }

    showTypingIndicator() {
        const typingDiv = document.createElement('div');
        typingDiv.id = 'typing-indicator';
        typingDiv.className = 'mb-4';
        
        const bubble = document.createElement('div');
        bubble.className = 'inline-block bg-gray-200 px-4 py-2 rounded-lg';
        bubble.innerHTML = '<div class="typing-animation"><span></span><span></span><span></span></div>';
        
        typingDiv.appendChild(bubble);
        this.messagesContainer.appendChild(typingDiv);
        this.scrollToBottom();
    }

    hideTypingIndicator() {
        const typingIndicator = document.getElementById('typing-indicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    scrollToBottom() {
        this.messagesContainer.scrollTop = this.messagesContainer.scrollHeight;
    }
}

// Initialize chatbot when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdidasChatbot();
});

// Add styles for typing animation
const style = document.createElement('style');
style.textContent = `
    .typing-animation {
        display: flex;
        gap: 4px;
    }
    
    .typing-animation span {
        width: 6px;
        height: 6px;
        background-color: #666;
        border-radius: 50%;
        animation: typing 1s infinite ease-in-out;
    }
    
    .typing-animation span:nth-child(1) { animation-delay: 0.2s; }
    .typing-animation span:nth-child(2) { animation-delay: 0.4s; }
    .typing-animation span:nth-child(3) { animation-delay: 0.6s; }
    
    @keyframes typing {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-6px); }
    }
`;
document.head.appendChild(style);
