#!/usr/bin/env python3

import requests
import json
import os
import sys
from pathlib import Path
import argparse
from datetime import datetime
import time

class PlexDataFetcher:
    def __init__(self, plex_url, plex_token, output_dir="data"):
        self.plex_url = plex_url.rstrip('/')
        self.plex_token = plex_token
        self.output_dir = Path(output_dir)
        self.setup_directories()
        self.session = requests.Session()
        self.session.headers.update({
            'X-Plex-Token': self.plex_token,
            'Accept': 'application/json'
        })

    def setup_directories(self):
        """Create necessary directory structure"""
        directories = [
            self.output_dir,
            self.output_dir / "posters" / "movies",
            self.output_dir / "posters" / "tvshows"
        ]
        for directory in directories:
            directory.mkdir(parents=True, exist_ok=True)

    def fetch_sections(self):
        """Get all library sections"""
        try:
            response = self.session.get(f"{self.plex_url}/library/sections")
            response.raise_for_status()
            return response.json()
        except requests.RequestException as e:
            print(f"Error fetching sections: {e}")
            return None

    def fetch_section_content(self, section_key):
        """Fetch all content from a specific section"""
        try:
            # Fetch all items at once
            response = self.session.get(f"{self.plex_url}/library/sections/{section_key}/all")
            response.raise_for_status()
            return response.json()
        except requests.RequestException as e:
            print(f"Error fetching section content: {e}")
            return None

    def download_image(self, image_url, output_path):
        """Download an image to the specified path"""
        if not image_url:
            return False
        
        try:
            # Remove existing file if it exists
            if output_path.exists():
                os.remove(output_path)
            
            response = self.session.get(f"{self.plex_url}{image_url}")
            response.raise_for_status()
            
            with open(output_path, 'wb') as f:
                f.write(response.content)
            
            return True
        except requests.RequestException as e:
            print(f"Error downloading image {image_url}: {e}")
            return False

    def process_media_item(self, item, media_type):
        """Process a single media item and extract relevant data"""
        try:
            # Extract common fields
            media_info = {
                'id': str(item.get('ratingKey', '')),
                'title': item.get('title', ''),
                'year': item.get('year', ''),
                'summary': item.get('summary', ''),
                'rating': item.get('rating', ''),
                'studio': item.get('studio', ''),
                'addedAt': item.get('addedAt', ''),
                'updatedAt': item.get('updatedAt', '')
            }
            
            if media_type == 'movie':
                media_info.update({
                    'duration': item.get('duration', ''),
                    'contentRating': item.get('contentRating', ''),
                    'originallyAvailableAt': item.get('originallyAvailableAt', '')
                })
            elif media_type == 'tvshow':
                media_info.update({
                    'leafCount': item.get('leafCount', ''),  # episode count
                    'childCount': item.get('childCount', ''),  # season count
                    'studio': item.get('studio', '')
                })
            
            return media_info
        except Exception as e:
            print(f"Error processing media item: {e}")
            return None

    def fetch_and_save_data(self):
        """Main method to fetch all data and save it"""
        print(f"Starting Plex data fetch at {datetime.now()}")
        
        # Get all sections
        sections_data = self.fetch_sections()
        if not sections_data or 'MediaContainer' not in sections_data:
            print("Failed to fetch sections")
            return
        
        sections = sections_data['MediaContainer'].get('Directory', [])
        
        movies_data = []
        tvshows_data = []
        
        for section in sections:
            section_key = section.get('key')
            section_type = section.get('type')
            section_title = section.get('title')
            
            print(f"\nProcessing section: {section_title} (Type: {section_type})")
            
            if section_type not in ['movie', 'show']:
                print(f"Skipping unsupported section type: {section_type}")
                continue
            
            # Fetch content for this section
            content_data = self.fetch_section_content(section_key)
            if not content_data or 'MediaContainer' not in content_data:
                continue
            
            items = content_data['MediaContainer'].get('Metadata', [])
            print(f"Found {len(items)} items in {section_title}")
            
            for item in items:
                media_type = 'movie' if section_type == 'movie' else 'tvshow'
                media_info = self.process_media_item(item, media_type)
                
                if media_info:
                    # Determine output paths
                    poster_dir = self.output_dir / "posters" / f"{media_type}s"
                    poster_path = poster_dir / f"{media_info['id']}.jpg"
                    
                    # Download poster
                    poster_url = item.get('thumb')
                    if poster_url:
                        if self.download_image(poster_url, poster_path):
                            print(f"Downloaded poster for: {media_info['title']}")
                        else:
                            print(f"Failed to download poster for: {media_info['title']}")
                    
                    # Add to appropriate list
                    if media_type == 'movie':
                        movies_data.append(media_info)
                    else:
                        tvshows_data.append(media_info)
        
        # Save JSON files
        movies_file = self.output_dir / "movies.json"
        tvshows_file = self.output_dir / "tvshows.json"
        
        with open(movies_file, 'w') as f:
            json.dump(movies_data, f, indent=2)
        
        with open(tvshows_file, 'w') as f:
            json.dump(tvshows_data, f, indent=2)
        
        print(f"\nData fetch completed at {datetime.now()}")
        print(f"Movies: {len(movies_data)}")
        print(f"TV Shows: {len(tvshows_data)}")
        print(f"Data saved to: {self.output_dir}")

def main():
    parser = argparse.ArgumentParser(description='Fetch Plex media data and posters')
    parser.add_argument('--url', required=True, help='Plex server URL (e.g., http://localhost:32400)')
    parser.add_argument('--token', required=True, help='Plex authentication token')
    parser.add_argument('--output', default='data', help='Output directory (default: data)')
    
    args = parser.parse_args()
    
    fetcher = PlexDataFetcher(args.url, args.token, args.output)
    fetcher.fetch_and_save_data()

if __name__ == "__main__":
    main()
