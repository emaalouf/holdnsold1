export const sharePlatforms = {
    facebook: 'Facebook',
    twitter: 'Twitter',
    whatsapp: 'WhatsApp',
    telegram: 'Telegram',
    email: 'Email'
};

export const shareAuction = async (auctionId, platform) => {
    try {
        const response = await axios.get(`/api/auctions/${auctionId}/share/${platform}`);
        window.open(response.data.share_url, '_blank');
        return true;
    } catch (error) {
        console.error('Error sharing auction:', error);
        return false;
    }
};

export const getShareStats = async (auctionId) => {
    try {
        const response = await axios.get(`/api/auctions/${auctionId}/share-stats`);
        return response.data;
    } catch (error) {
        console.error('Error getting share stats:', error);
        return null;
    }
}; 